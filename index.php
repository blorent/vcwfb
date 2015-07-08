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

function get_portailaif_content($team)
{
	$club = '5083';
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
	return $response;
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
		# Skip the "bye" matches
		if (substr($match['home'], 0, 3) == "Bye" || substr($match['visitor'], 0, 3) == "Bye" )
			continue;

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

function highlight_team($team_name)
{
	if (substr($team_name, 0, 10) == "VC Walhain")
		return "<b>Walhain</b>";
	else
		return $team_name;
}

function print_matches($matches_array)
{
	setlocale(LC_TIME, 'fr_BE');
	$day_to_fr = array("Monday" => "Lundi", "Tuesday" => "Mardi", "Wednesday" => "Mercredi", "Thursday" => "Jeudi", "Friday" => "Vendredi", "Saturday" => "Samedi", "Sunday" => "Dimanche");
	$day_to_fr_short = array("Monday" => "Lu", "Tuesday" => "Ma", "Wednesday" => "Me", "Thursday" => "Je", "Friday" => "Ve", "Saturday" => "Sa", "Sunday" => "Di");
	foreach ($matches_array as $match)
	{
		echo "<p class='match'>" . $day_to_fr_short[$match['date']->format('l')] . "&nbsp;" . $match['date']->format('d/m/Y') ."&nbsp;&nbsp;&nbsp;" . $match['date']->format('H:i') . "<span class=match>" . highlight_team($match['home']) . " - " . highlight_team($match['visitor']) . "</span></p>";
	}
}

 ?>

 <div class="wrapper">
 <div class="maincontent">

	<?php

	$teams = array('N0BM/VC Walhain' => 'Ligue B', 'N2M/VC Walhain' => 'Nationale 2', 'P1M/VC Walhain C' => 'Provinciale 1');

	foreach ($teams as $team_id => $team_name) {
		echo '<div class=calendar>';
		echo '<h1 class=cal>Calendrier '. $team_name .'</h1>';
		$page = get_portailaif_content($team_id);
		$cal = parsePage($page);
		print_matches($cal);
		echo '</div>';
	}	

	?>

 </div>
 </div>

 </body>
</html>