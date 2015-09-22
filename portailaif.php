<?php 

require_once 'MatchesRetriever.interface.php';

class PortailAIFParser implements MatchesRetriever
{
	public function getAllMatchesForDivision($division_id)
	{
		$page = $this->get_portailaif_calendar_content($division_id);
		return $this->parse_page($page);
	}



	private function extractDate($datestr, $timestr)
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

	private $to_portailaif_code = array('N0BM' => 'N0BM/VC Walhain A', 'N2M' => 'N2M/VC Walhain B', 'P1M' => 'P1M/VC Walhain C');

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

	private function parse_page($content)
	{
		$tags = array('titdiv', 'centre m9_x', 'centre m9_0', 'droite td11', 'td11');
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

		foreach($all_matches as $this_match)
		{
			# Skip the "bye" matches
			if (substr($this_match['home'], 0, 3) == "Bye" || substr($this_match['visitor'], 0, 3) == "Bye" )
				continue;

			# Remove (Weekend des)
			$clean_date = substr($this_match['date'], $this->digit_offset($this_match['date']));

			# Get the date of the sunday of the specified weekend
			$sunday = $this->extractDate(trim(substr($clean_date, strpos($clean_date, '-') + 1)), $this_match['time']);

			# Remove the necessary number of days
			$days_to_subtract = array_search($this_match['day'], $offset_to_sunday);
			$interval_format = 'P' . (string) $days_to_subtract . 'D';
			$interval = new DateInterval($interval_format);
			$match_date = $sunday->sub($interval);

			# Add the date to the output array
			$out_matches[$match_idx]['date'] = $match_date;

			# Add the teams playing to the output array
			$out_matches[$match_idx]['home'] = $this_match['home'];
			$out_matches[$match_idx]['visitor'] = $this_match['visitor'];
			$match_idx++;
		}

		return $out_matches;
	}
}

?>