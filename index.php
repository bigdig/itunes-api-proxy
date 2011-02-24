<?php

/**
 * API for proxying search and meda requests from Flash to the 
 * iTunes Store API (since it does not host crossdomain.xml files)
 *
 * @author Daniel Leavitt
 */

require_once('libs/hermosa.php');

########################################
##  SETTINGS
########################################

$settings = array(
	// string to look for in the URL to figure out if we're local or live
	'environment' => array(
		'local' => array('localhost', '10.1.1'),
		'stage' => 'stage.example.com',
		'live' => 'example.com',
	),
	'config' => array(
		'db' => array(
			'local' => array(
				'host' => 'localhost',
				'db' => '',
				'user' => '',
				'pass' => ''
			),
			'stage' => array(
				'host' => 'localhost',
				'db' => '',
				'user' => '',
				'pass' => ''
			),
			'live' => array(
				'host' => 'localhost',
				'db' => '',
				'user' => '',
				'pass' => ''
			),
		),
		'cache' => array(
			'default' => array(
				'table' => 'search_cache',
				'lifetime' => 86400, // 1 day
			),
		),
		'base_url' => array(
			'local' => 'http://localhost:8888/example.com',
			'stage'	=> 'http://stage.example.com',
			'live' 	=> 'http://example.com',
		)
	),
);

########################################
##  ACTIONS
########################################

// get the main page

function action_index()
{
	$id = arr($_GET, 'id');
	return render('views/index.html', array('id' => $id));	
}

// convert hash into iTunes media URL
// no crossdomain on itunes server so we have to proxy the request here

function action_media($hash)
{
	$url = 'http://a1.phobos.apple.com/us/'.base64_decode($hash);
	
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL					=> $url,
		CURLOPT_FOLLOWLOCATION		=> TRUE,
		CURLOPT_RETURNTRANSFER		=> TRUE,
	));
	$response = curl_exec($ch);
	curl_close($ch);
	
	include('libs/mimes.php');
	$mime = arr($mimes, pathinfo($url, PATHINFO_EXTENSION), array('text/html'));
	$mime = $mime[0];
	
	header("Content-Type: ".$mime);
	echo $response;
}

function action_playlist($id = NULL)
{
	return $_SERVER['REQUEST_METHOD'] == 'POST' ? post_playlist() : get_playlist($id);
}

	// GET playlist with the given ID
	// eg GET /playlist/1
	
	function get_playlist($id)
	{
		try
		{
			$pdo = pdo_connect();
		
			$query = $pdo->prepare('SELECT data FROM playlists WHERE id = ?');
			$query->execute(array($id));
			$result = $query->fetch();
		
			if ($result['data'])
			{
				return send_response(TRUE, json_decode($result['data']));
			}
		
		}
		catch (Exception $e)
		{
			error_log($e);
		}
	
		return send_response(FALSE);
	}


	// POST a playlist
	// return the id
	// post it with raw JSON as the body, not form-encoded
	
	function post_playlist()
	{
		$data = file_get_contents('php://input');
	
		try
		{
			$pdo = pdo_connect();
		
			$query = $pdo->prepare('INSERT INTO playlists(data) VALUES (?)');
			$query->execute(array($data));
			if ($id = $pdo->lastInsertId())
			{
				return send_response(TRUE, $id);
			}
		}
		catch (Exception $e) 
		{
			error_log($e);
		}
	
		return send_response(FALSE);
	}

// search itunes store for matching tracks
// caches searches for 24 hours to avoid making a zillion API calls
// returns an array of object with the following fields:
// artistName, artworkUrl100, artworkUrl60, artworkUrl30
// collectionName, previewUrl, trackName, trackViewUrl

function action_search()
{
	$query = arr($_GET, 'q');
	
	if ( ! $query)
	{
		return send_response(FALSE);
	}
	
	if ($response = cache_get('search_'.$query))
	{
		return send_response(TRUE, $response);
	}
	
	$params = http_build_query(array(
		'term' 			=> $query,
		'media' 		=> 'music',
		'entity'		=> 'musicTrack',
		'limit'			=> 25,
	));
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL					=> 'http://ax.itunes.apple.com/WebObjects/MZStoreServices.woa/wa/wsSearch?'.$params,
		CURLOPT_FOLLOWLOCATION		=> TRUE,
		CURLOPT_RETURNTRANSFER		=> TRUE,
	));
	$raw_response = curl_exec($ch);
	curl_close($ch);
	
	try
	{
		$response = json_decode($raw_response, TRUE);
		
		foreach ($response['results'] as & $track)
		{
			$result = array();
			
			foreach (array('trackName', 'artistName', 'collectionName', 'trackViewUrl') as $key)
			{
				$result[$key] = $track[$key];
			}
			
			foreach (array('previewUrl', 'artworkUrl30', 'artworkUrl60', 'artworkUrl100') as $key)
			{
				$result[$key] = generate_proxy_url(arr($track, $key));
			}
			
			$results[] = $result;
		}
		
		cache_set('search_'.$query, $results);
		
		return send_response(TRUE, $results);
	}
	catch (Exception $e) 
	{
		return send_response(FALSE);
	}
}

// clear results for recent searches

function action_clear_cache()
{
	$pdo = pdo_connect();
	$pdo->exec('TRUNCATE TABLE `search_cache`');
	return send_response(TRUE);
}

########################################
##  HELPERS
########################################

function generate_proxy_url($url)
{
	return '/media/'.urlencode(base64_encode(str_replace('http://a1.phobos.apple.com/us/', '', $url)));
}

########################################
run($settings);
########################################
