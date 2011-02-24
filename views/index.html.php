<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Title</title>
	<meta name="title" content="Title" />
	<meta name="description" content="Description" />
	<meta name="medium" content="video" />
	<link rel="video_src" href="<?= conf('base_url').'/swf/Main.swf' ?>" />
	<meta name="video_height" content="380" />
	<meta name="video_width" content="500" />
	<meta name="video_type" content="application/x-shockwave-flash" />
	<link rel="image_src" href="<?= conf('base_url').'/img/preview.png' ?>" />
	<meta property="og:image" content="<?= conf('base_url').'/img/preview.png' ?>" />
	<meta name="author" content="HYFN">
	<style type="text/css" media="screen">
		* {
			margin: 0;
			padding: 0;
		}
		body {
			font-family: arial, helvetica, sans-serif;
		}
		 #swf_container {
			margin: 10px auto;
			border: 1px solid #C1B8BB;
			width: 380px;
			height: 500px;	
		}
	</style>
</head>
<body>
	<div id="swf_container">
		<div id="swf_replace"></div>
	</div>
	<script src="http://ajax.googleapis.com/ajax/libs/swfobject/2.2/swfobject.js"></script>
	<script>
	
		window.log = function(){
		  log.history = log.history || []; 
		  log.history.push(arguments);
		  if(this.console){
		    console.log( Array.prototype.slice.call(arguments) );
		  }
		};
		
		var flashVars = {
			debug: swfobject.getQueryParamValue('debug'),
			playlistID: swfobject.getQueryParamValue('id')
		};
		
		var params = {
			menu: false,
			quality: "high",
			wmode: "opaque",
			allowscriptaccess: "always",
			allownetworking: "all"
		};
		
		var attributes = {
			id: "flash_content",
			name: "flash_content"
		};
		
		// Fix for FF 3 w/o Firebug.
		setTimeout(function() {
			swfobject.embedSWF(flashVars.debug ? 'swf/debug/Main.swf' : 'swf/Main.swf', 
				"swf_replace", "380", "500", "10.0.0", 'swf/expressInstall.swf', flashVars, params, attributes);
		}, 100);
		
	</script>
	
	<script>
	// var _gaq = [['_setAccount', 'UA-19780472-4'], ['_trackPageview']]; 
	// (function(d, t) {
	// 	var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
	// 	g.async = true; g.src = '//www.google-analytics.com/ga.js'; s.parentNode.insertBefore(g, s);
	// })(document, 'script');
	// 
	// _gaq.push(['_trackEvent', 'impression', 'page']);
	
	</script>
	
</body>
</html>