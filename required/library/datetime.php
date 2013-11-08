<?php


/**
 * Advanced DateTime Class
 */
class AdvancedDateTime extends DateTime {

	/**
	 * Creates new object
	 * @param String		$time			Date string
	 * @param String		$errorMessage	Exception text throw in case of error
	 * @param DateTimeZone	$timeZone		Zone like in real DateTime
	 * @throws userErrorException
	 */
	public function __construct($time = "now", $errorMessage = "Не верно указана дата", $timeZone = null) {
		try {
			
			# Create real DateTime
			if($timeZone)	parent::__construct($time, $timeZone);
			else			parent::__construct($time);
			
		# In case of creation error
		} catch(Exception $e) {
			throw new userErrorException($errorMessage);	
		}
		
	}
	
	/**
	 * Creates new object
	 * @param String		$time			Date string
	 * @param String		$errorMessage	Exception text throw in case of error
	 * @param DateTimeZone	$timeZone		Zone like in real DateTime
	 * @return \AdvancedDateTime
	 * @throws userErrorException
	 */
	public static function make($time = "now", $errorMessage = "Не верно указана дата", $timeZone = null) {
		return new AdvancedDateTime($time, $errorMessage, $timeZone);
	}
	
	/**
	 * Alter the timestamp of a DateTime object by incrementing or decrementing in a format accepted by strtotime().
	 * @param String $modify A date/time string. Valid formats are explained in Date and Time Formats.
	 * @return \AdvancedDateTime
	 */
	public function modify($modify) {
	
		# Modify
		parent::modify($modify);
		
		# Return
		return $this;
		
	}

	/**
	 * Removes specified parts
	 * @param Bool $hours	If true sets hours to 0
	 * @param Bool $minutes If true sets minutes to 0
	 * @param Bool $seconds If true sets seconds to 0
	 * @return $this
	 */
	public function trim($hours = true, $minutes = true, $seconds = true) {
		
		# Remove hours
		if($hours)
			$this->modify("- ".($this->format("H"))." hour");
		
		# Remove minutes
		if($minutes)
			$this->modify("- ".($this->format("i"))." minute");
		
		# Remove seconds
		if($seconds)
			$this->modify("- ".($this->format("s"))." second");
		
		# Return
		return $this;
		
	}

	/**
	 * Get diffirence between two dates
	 * @param bool|\DateTime $date Date to take diffirence, if false now will be taken
	 * @param bool           $absolute
	 * @return \DateInterval
	 */
	public function diff($date = false, $absolute = false) {
		
		# Create now if needed
		if($date === false)
			$date = new AdvancedDateTime();

		# String to datetime
		if(is_string($date))
			$date = new AdvancedDateTime($date);

		# Count difference
		return parent::diff($date, $absolute);
		
	}

	public function russian($format = "d.m.Y H:i") {

		# Replace format
		$format = str_replace("M", "М", $format);

		# Get
		$return = $this->format($format);

		# Names
		$rusMonths = Array('Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля', 'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря');

		return str_replace("М", $rusMonths[(int)$this->format("m")], $return);

	}

}
