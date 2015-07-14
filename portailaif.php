<?php

require_once 'MatchesRetriever.interface.php'

class PortailAIFParser implements MatchesRetriever
{
	public function getAllMatchesForDivision($division_id)
	{
		if !isset($this->division_id)
			return false;

		$page = get_portailaif_calendar_content($division);
		return parsePage($page);
	}



	

	private $to_portailaif_code = array('N0BM' => 'N0BM/VC Walhain', 'N2M' => 'N2M/VC Walhain', 'P1M' => 'P1M/VC Walhain C');

	private function get_portailaif_results_content($team)
	{
		$url='http://portailaif.be/vbClassement.php';

		$myvars = 'div=' . $team . '&full=1';
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

	private function get_portailaif_calendar_content($team)
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

	private function digit_offset($text)
	{
	    preg_match('/^\D*(?=\d)/', $text, $m);
	    return isset($m[0]) ? strlen($m[0]) : false;
	}

	private function parsePage($content)
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
}

?>