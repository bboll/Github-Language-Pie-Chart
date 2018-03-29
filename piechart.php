<!DOCTYPE html>
<html lang="en">
  <head>
    <?php
    
	  //repos_url = Github API user ID + auth token
      $repos_url = "";
      $ch = curl_init($repos_url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_USERAGENT, "Language graph used for bboll's personal site");

      $response = curl_exec($ch);
      curl_close($ch);
	
      $repoList = json_decode($response);
      
      $langArray = [];
      foreach($repoList as $repo)
      {
		//concatenate auth token
      	$languages_url = $repo->languages_url . "?client_id=";
      	$ch = curl_init($languages_url);
      	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      	curl_setopt($ch, CURLOPT_USERAGENT, "Language graph used for user bboll's personal site");
      	$response = curl_exec($ch);
      	curl_close($ch);
      	
      	$langList = json_decode($response);
      	
      	foreach($langList as $language => $bytes)
      	{
      	  if(array_key_exists($language, $langArray))
      	  {
      	    $langArray[$language] += $bytes;
      	  }
      	  else
      	  {
      	    $langArray[$language] = $bytes;
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
    
    <script type="text/javascript">
    <?php echo 'var langJSON=' . json_encode($langArray); ?>
    
    var data = [];
    var pieTotal = 0;
    var piecolors = [ "#337ab7", "#5cb85c", "#f0ad4e", "#d9534f", "#292E37"];

    $.each(langJSON, function(language, bytes) {
      data.push({
        bytes: bytes,
        language: language
      });
      pieTotal += bytes;
    });

			var canvas;
			var ctx;
			var lastend = 0;
			
		
			canvas = document.getElementById("canvas");
			ctx = canvas.getContext("2d");
			
			ctx.clearRect(0, 0, canvas.width, canvas.height);
			
			var hwidth = ctx.canvas.width/2;
			var hheight = ctx.canvas.height/2;
			
			
			for (var i = 0; i < data.length; i++) {
				ctx.fillStyle = piecolors[i % 5];
				ctx.beginPath();
				ctx.moveTo(hwidth,hheight);
				ctx.arc(hwidth,hheight,hheight,lastend,lastend+
				  (Math.PI*2*(data[i]["bytes"]/pieTotal)),false);
				
				
				ctx.lineTo(hwidth,hheight);
				ctx.fill();
				
				//Labels on pie slices (fully transparent circle within outer pie circle, to get middle of pie slice)
				ctx.beginPath();
				ctx.moveTo(hwidth,hheight);
				ctx.arc(hwidth,hheight,hheight/1.25,lastend,lastend+(Math.PI*(data[i]["bytes"]/pieTotal)),false); 
				
				//Use suitable radius
				var radius = hheight/1.5;
				var endAngle = lastend + (Math.PI*(data[i]["bytes"]/pieTotal));
				var setX = hwidth + Math.cos(endAngle) * radius;
				var setY = hheight + Math.sin(endAngle) * radius;
				ctx.fillStyle = "#ffffff";
				ctx.font = '14px Calibri';
				console.log(setX + ", " + setY);
				ctx.fillText(data[i]["language"],setX,setY);
				
				ctx.lineTo(hwidth,hheight);
				
				lastend += Math.PI*2*(data[i]["bytes"]/pieTotal);
			}	
    
    
    </script>
 

  </div>
  </body>
</html>
