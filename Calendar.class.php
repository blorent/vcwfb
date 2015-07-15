<?php

require_once 'Match.class.php'
require_once 'portailaif.php'


class Calendar
{
	public $division_id; 	// For portailaif parsing
	public $division_name;	// Name of the division to display
	public $matches;

	private $portailaif_getter = new PortailAIFParser();
	private $sql_getter = new SQLInterface();

	public function __construct($division_id, $division_name)
	{
		$this->division_id = $division_id;
		$this->division_name = $division_name;
		$this->matches = getAllMatches();
	}

	private function getAllMatches()
	{
		// Test if what we have in cache is not too old
		$today = new DateTime("now", new DateTimeZone("Europe/Brussels"));
		if (GetSQLTableLastUpdate() < $today->sub(new DateInterval("P1D")))
			$getter = new PortailAIFParser();
		else
			$getter = new 

		$getter->getAllMatchesForDivision($division_id);

	}
	
}

?>