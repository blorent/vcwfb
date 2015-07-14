<?php

require_once 'Match.class.php'
require_once 'portailaif.php'


class Calendar
{
	public $division_id; 	// For portailaif parsing
	public $division_name;	// Name of the division to display
	public $matches;

	public function __construct($division_id, $division_name)
	{
		$this->division_id = $division_id;
		$this->division_name = $division_name;
		$this->matches = getAllMatches();
	}

	private function getAllMatches()
	{
		// Test if what we have in cache is not too old

		// If yes, get the calendar from portailaif
		$getter = new PortailAIFParser();
		$getter->getAllMatchesForDivision($division_id);

	}
	
}

?>