<html>
 <head>
  <title>Calendriers</title>
  <link rel="stylesheet" href="style.css">
 </head>
 <body>

 <?php

 function digit_offset($text){
    preg_match('/^\D*(?=\d)/', $text, $m);
    return isset($m[0]) ? strlen($m[0]) : false;
}

function extractDate($datestr, $timestr)
{
	$split = explode(' ', $datestr);
	$months = ['dummy', 'janvier', 'fevrier', 'mars', 'avril', 'mai', 'juin', 'juillet', 'aout', 'septembre', 'octobre', 'novembre', 'decembre'];
	$months_short = ['dummy', 'jan', 'fev', 'mars', 'avr', 'mai', 'juin', 'juillet', 'aout', 'sep', 'oct', 'nov', 'dec'];
	$day = $split[0];
	$month = array_search(strtr(strtolower($split[1]), chr(233), 'e'), $months);
	# Probably short form
	if ($month == FALSE)
	{
		$month = array_search(strtr(strtolower($split[1]), chr(233), 'e'), $months_short);
	}
	$year = $split[2];

	return DateTime::createFromFormat('Y-m-d H:i', $year . '-' . $month . '-' . $day . ' ' . $timestr, new DateTimezone('Europe/Brussels'));
}

function parsePage($content)
{
	$tags = array('titdiv', 'centre m9_0', 'droite td11', 'td11');
	$keys = array('date', 'day', 'time', 'home', 'visitor');

	$all_matches = array();
	$match_idx = 0;
	$local_idx = 0;

	# Keep only the relevant lines, and build an array of values out of them
	foreach(preg_split("/((\r?\n)|(\r\n?))/", $content) as $line)
	{
		foreach($tags as $tag)
		{
			if (strstr($line, $tag) != false)
			{
				$line = strip_tags(trim($line));

				if ($tag == $tags[0])
				{
					$local_idx = 0;
					$match_idx++;
				}

				$all_matches[$match_idx][$keys[$local_idx]] = $line;
				$local_idx++;

				break;
			}
		}    	
	}

	# Post process
	$offset_to_sunday = array('DI', 'SA', 'VE', 'JE', 'ME', 'MA', 'LU');
	$out_matches = array();
	$match_idx = 0;

	foreach($all_matches as $match)
	{
		# Remove (Weekend des)
		$clean_date = substr($match['date'], digit_offset($match['date']));

		# Get the date of the sunday of the specified weekend
		$sunday = extractDate(trim(substr($clean_date, strpos($clean_date, '-') + 1)), $match['time']);

		# Remove the necessary number of days
		$match_date = $sunday->sub(new DateInterval('P'. array_search($match['day'], $offset_to_sunday) . 'D'));

		# Add the date to the output array
		$out_matches[$match_idx]['date'] = $match_date;

		# Add the teams playing to the output array
		$out_matches[$match_idx]['home'] = $match['home'];
		$out_matches[$match_idx]['visitor'] = $match['visitor'];
		$match_idx++;
	}

	return $out_matches;
}

 ?>

 <div class="wrapper">
 <div class="maincontent">

	<h1>Calendriers</h1>

	<?php

	# Get the corresponding page on portailaif
	$club = '5083';
	$team = 'N0BM/VC Walhain';
	$url='http://portailaif.be/vbLstByEq.php';

	$myvars = 'LstClb=' . $club . '&OLstClb=' . $club . '&LstEq=' . $team;
	$ch = curl_init( $url );
	curl_setopt( $ch, CURLOPT_POST, 1);
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $myvars);
	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt( $ch, CURLOPT_HEADER, 0);
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt( $ch, CURLOPT_REFERER, 'http://www.portailaif.be');
	$response = curl_exec( $ch );

	# Extract relevant info
	$cal = parsePage($response);

	# Display it
	setlocale(LC_TIME, 'fr_BE');
	$day_to_fr = array("Monday" => "Lundi", "Tuesday" => "Mardi", "Wednesday" => "Mercredi", "Thursday" => "Jeudi", "Friday" => "Vendredi", "Saturday" => "Samedi", "Sunday" => "Dimanche");
	foreach ($cal as $match)
	{
		echo "<p class='match'>" . $day_to_fr[$match['date']->format('l')] . " " . $match['date']->format('d/m/Y H:i') . " : " . $match['home'] . " - " . $match['visitor'] . "</p>";
	}

	?>

 </div>
 </div>

 </body>
</html>