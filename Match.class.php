<?php

class Match
{
	public $division;
	public $datetime;
	public $home;
	public $visitor;

	public $home_result;
	public $visitor_result;

	public function __construct($division, $datetime, $home, $visitor)
	{
		$this->datetime = $datetime;
		$this->home = $home;
		$this->visitor = $visitor;
		$this->home_result = "NC";
		$this->visitor_result = "NC";
	}

	public function __toString()
	{
		return $this->datetime->format('l') . " " . $this->datetime->format('d/m/Y') . " " . $this->datetime->format('H:i') . " " . $this->home . " - " . $this->visitor . " (" . $this->home_result . "-" . $this->visitor_result . ")";
	}

	public function setScore($home_result, $visitor_result)
	{
		$this->home_result = $home_result;
		$this->visitor_result = $visitor_result;
	}

	public function hasScore()
	{
		return ($this->home_result != "NC");
	}

	public function getScore()
	{
		return $this->home_result . "-" . $this->visitor_result;
	}

	public function isPassed()
	{
		return ($this->datetime = new DateTIme('now', new DateTimeZone('Europe/Brussels')));
	}
}

?>