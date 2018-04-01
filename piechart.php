<!DOCTYPE html>
<html lang="en">
   <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Brian Boll">
    <link rel="stylesheet" type="text/css" href="stylesheet.css">

    <title>Github Language Pie Chart</title>
    <?php
    	include_once('config.php');
	
	$APP_ID = $config["clientID"];
	$APP_SECRET = $config["clientsecret"];
	  
  	if(!isset($_POST['access_token']) && !isset($_GET['code'])) {
	  header('Location: https://github.com/login/oauth/authorize?client_id=' . $APP_ID);
 	} 
 	else {
	  //Request to turn temp code into auth token for user
	  $auth_url = "https://github.com/login/oauth/access_token";
	  
	  $ch = curl_init();
      	  curl_setopt($ch, CURLOPT_USERAGENT, "LanguageGraph");

	  $post = [
		'code' => $_GET['code'],
		'client_id' => $APP_ID,
		'client_secret'   => $APP_SECRET,
		'redirect_uri' => "http://www.brianboll.com/piechart/piechart.php",
	  ];
	  
	  curl_setopt($ch, CURLOPT_URL,$auth_url);
	  curl_setopt($ch, CURLOPT_POST, true);
	  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	  $response = curl_exec ($ch);
	  curl_close ($ch);
	  
	  //Parse input into delineated tokens
	  $str_tokens = substr_replace($response,'&',12,1);
	  $tokens = explode("&", $str_tokens);
	  $access_token = $tokens[1];
	  
	  $header = array();
	  $header[] = 'Authorization: token ' . $access_token;
	  
	  $repos_url = "https://api.github.com/user/repos";
	  $ch = curl_init();
      
	  curl_setopt($ch, CURLOPT_URL, $repos_url);
	  curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	  curl_setopt($ch, CURLOPT_POST,       false);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	  curl_setopt($ch, CURLOPT_USERAGENT, "LanguageGraph");
	  
	  $response = curl_exec($ch);
      	  curl_close($ch);
	
      	  $repoList = json_decode($response);
      
      	  $langArray = [];
      	  foreach($repoList as $repo)
      	  {
	    //Concatenate auth token
      	    $languages_url = $repo->languages_url;
      	    $ch = curl_init();	
      	    curl_setopt($ch, CURLOPT_URL, $languages_url);
      	    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
      	    curl_setopt($ch, CURLOPT_POST,       false);
      	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      	    curl_setopt($ch, CURLOPT_USERAGENT, "LanguageGraph");
      	
      	    $response = curl_exec($ch);
      	    curl_close($ch);
      	
      	    $langList = json_decode($response);
      	
      	    foreach($langList as $language => $bytes)
      	    {
      	        $langArray[$language]['bytes']+= $bytes;
      	    }
          }
  	}
    ?>
  </head>
  <body>
   <div id="container">
    <div id="header">
    </div>

    <div id="content">
    </div>
    <canvas id="canvas" width="500" height="500"></canvas>
    
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script type="text/javascript">
    <?php 
      $str = file_get_contents('colors.json'); 

      $langColors = json_decode($str, true);
      $tmpArray;

      //Find subset of languages and lookup associating colors
      foreach($langArray as $lang => $value)
      {
          $tmpObj['bytes'] = $langArray[$lang]['bytes'];
          $tmpObj['color'] = $langColors[$lang]['color'];
          
          $langArray[$lang] = $tmpObj;
      }
      
      echo 'var langJSON=' . json_encode($langArray); 
      
      
    ?>
    
    var data = [];
    var pieTotal = 0;
    
    //No longer need an associative structure
    $.each(langJSON, function (language) {
      var bytes;
      var color;
      $.each(langJSON[language], function(key, value) {
        switch (key)
        {
      	  case 'bytes':
      	    bytes = value; 
      	    pieTotal += value;
      	    break;
      	
      	  case 'color':
      	    color = value;
      	    break;
        }
      });
    
      data.push({
        bytes: bytes,
        language: language,
        color: color
      });
    });

    var canvas = document.getElementById("canvas");
    var ctx = canvas.getContext("2d");
	
    ctx.clearRect(0, 0, canvas.width, canvas.height);
	
    //Find the center point of the pie chart, origin
    var hwidth = ctx.canvas.width/2;
    var hheight = ctx.canvas.height/2;
	
    var lastend = 0;
    for (var i = 0; i < data.length; i++) {
      var lang = data[i]["language"];
      var slice = data[i]["bytes"];
      var color = data[i]["color"];

      ctx.fillStyle = color;
      ctx.beginPath();
      ctx.moveTo(hwidth,hheight);
      ctx.arc(hwidth,hheight,hheight,lastend,(lastend+(Math.PI*2)*(slice/pieTotal)),false);
		
      //Smoothing the lines between slices a bit
      ctx.lineCap = 'round';
      ctx.lineJoin = 'round';
      ctx.lineTo(hwidth,hheight);

      //Smooths the line but causes "acceptable amounts" of slice distortion, most noticeable at origin
      ctx.lineWidth = 0.1;
      ctx.strokeStyle = color;
      ctx.stroke();
      ctx.closePath();
      ctx.fill();
      
      lastend += Math.PI*2*(slice/pieTotal);
    }	
	
    //Iterate again otherwise long labels get pasted over by next slice
    lastend = 0;
    for (var i = 0; i < data.length; i++) {	
      var label = data[i]["language"];
      var slice = data[i]["bytes"];
      var color = data[i]["color"];
		
      //Labels on pie slices (fully transparent circle within outer pie circle, to get middle of pie slice)
      ctx.beginPath();
      ctx.moveTo(hwidth,hheight);
      ctx.arc(hwidth,hheight,hheight/1.25,lastend,lastend+(Math.PI*(slice/pieTotal)),false); 
		
      //Nice little stagger algorithm for labels helps prevent overlapping between small slices
      var radius = hheight/(1.5 + ((i%2)/10));
      var endAngle = lastend + (Math.PI*(slice/pieTotal));
      var setX = hwidth + Math.cos(endAngle) * radius;
      var setY = hheight + Math.sin(endAngle) * radius;
      ctx.font = "14px Calibri";
      ctx.shadowColor="#FFFFFF";
      ctx.shadowBlur=2;
      ctx.lineWidth=3;
      ctx.strokeStyle = "#000000";
      ctx.strokeText(label,setX,setY);
      ctx.shadowBlur=0;
      ctx.fillStyle=color;
      ctx.fillText(label,setX,setY);
		
      ctx.lineTo(hwidth,hheight);
      ctx.closePath();
		
      lastend += Math.PI*2*(slice/pieTotal);
    }
    
    </script>
 

  </div>
  </body>
</html>
