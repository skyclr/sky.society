<?php

/** Utilities for special needs */
class utilities {

	/**
	 * Generates dates information like associated array, keys:<br/>
	 * today,<br/> yesterday,<br/> thisWeekStart,<br/> thisWeekEnd,<br/> lastWeekStart,<br/> lastWeekEnd,<br/> thisWeekNo,<br/> lastWeekNo
	 */
	public static function getDates() {

		# Get this week start day
		$thisWeekStart = date("w") == 1 ? time() : strtotime("-1 monday");

		# Retrn dates information
		return array(
			"today" 			=> date('Y-m-d'),
			"yesterday" 		=> date('Y-m-d', strtotime('-1 day')),
			"thisWeekStart" 	=> date("Y-m-d", $thisWeekStart),
			"thisWeekEnd" 		=> date("Y-m-d", strtotime('Sunday')),
			"lastWeekStart" 	=> date("Y-m-d", strtotime('-1 monday', $thisWeekStart)),
			"lastWeekEnd" 		=> date("Y-m-d", strtotime('-1 sunday', $thisWeekStart)),
			"thisWeekNo" 		=> (int)date('W', $thisWeekStart),
			"lastWeekNo" 		=> (int)date('W', strtotime('-1 monday', $thisWeekStart))
		);

	}

	/**
	 * Returns date condition for request by period name
	 * @param String $period   Name of period to add conditions
	 * @param String $name     Name of column
	 * @param bool   $withTime
	 * @param string $tableName
	 * @return array
	 */
	public static function getDateConditions($period, $name = "date_short", $withTime = false, $tableName = "") {


		# Get dates of each standard period
		$dates = self::getDates();


		# Create objects
		switch($period) {
			case "today"	:
			case "yesterday": 	$date 	= new DateTime($dates[$period]); break;
			case "lastWeek"	:
			case "thisWeek" : 	$begin 	= new DateTime($dates[$period."Start"]);
				$begin->setTime(0, 0, 0);
				$end 	= new DateTime($dates[$period."End"]);
				$end->setTime("23", "59", "59");
				break;
			default: 			if(is_array($period)) {
				if(empty($period[0]) && empty($period[1])) break;
				if(!empty($period[0])) 	$begin = $period[0];
				if(!empty($period[1])) 	$end = $period[1];
				else 	 				$end = new DateTime();
			} else $date = $period;
		}


		# Set name
		if($withTime && $name === false) $name = "addate";
		if($withTime)	$format = DB2::DATETIME_SQL;
		else			$format = DB2::DATE_SQL;


		# Add table to name if needed
		if($tableName)
			$name = $tableName . '.' . $name;


		# Make conditions
		if(isset($date)) return array(array($name, $date->format($format)));
		else {

			# If no since and till
			if(!isset($begin) && !isset($end)) return array();

			# If only till
			if(!isset($begin)) return array(
				array($name, $end->format($format), "<=")
			);

			# Both conditions
			return array(
				array($name, $begin->format($format),	">="),
				array($name, $end->format($format),		"<=")
			);
		}

	}

	/**
	 * Replaces spaces with _ symbols
	 * @param String $str
	 * @return string replaced string
	 */
	public static function spaceToUnder($str) {
		return str_replace(" ", "_", str_replace("%20", "_", $str));
	}

	/**
	 * Replaces _ symbols with space
	 * @param String $str
	 * @return string replaced string
	 */
	public static function underToSpace($str) {
		return str_replace("_", " ", str_replace("%20", "_", $str));
	}

	/**
	 * Returns output buffer content, and stops buffering
	 * @return string buffer
	 */
	public static function ob_get_end_clean() {

		$content = ob_get_contents();
		ob_end_clean();
		return $content;

	}

	/**
	 * Generates random string of given length
	 * @param Int $length length of generated string, max - 32
	 * @return String - random string
	 */
	public static function getRandomString($length = 20) {


		# Set default length
		if(!is_numeric($length) || $length < 0)
			$length = 20;


		# Max 32
		if($length > 32)
			$length = 32;


		# MAke string
		return mb_substr(md5(mt_rand() . mt_rand()), 0, $length);

	}

	/**
	 * This function gets array keys $names[1] and $names[0] from $filter
	 * and return correct AdvancedDateTime objects which represent them, also
	 * correct them according to period, default period and max date period
	 * @param Array      $filter        Which have $names[1] and $names[0] keys values
	 * @param bool       $period        Maximum period between since and till
	 * @param Array|bool $names         Contains names to search in $filter
	 * @param bool       $defaultPeriod If since and till not set, if you set here "1 month", they will be today and today - 1 month
	 * @param string     $maxDatePeriod If since or till les than this period, then they will be set to it
	 * @throws Exception|systemException|userErrorException|systemErrorException
	 * @return array(AdvancedDateTime,  AdvancedDateTime)
	 */
	public static function getSinceAndTill($filter, $period = false, $names = false, $defaultPeriod = false, $maxDatePeriod = "1 year") {


		# If int then months
		if(is_int($period))
			$period = $period . " month";


		# Add month to default period
		if(is_int($defaultPeriod))
			$defaultPeriod = $defaultPeriod . " month";


		# Set default to max if no set
		if($defaultPeriod === false)
			$defaultPeriod = $period;


		# Set default names
		if(!$names)
			$names = array("since", "till");


		# Vars init
		$till 	= false;
		$since 	= false;
		$minDate= false;
		$today 	= new AdvancedDateTime();


		# Set minimum begin date
		if($maxDatePeriod) {
			$minDate = clone $today;
			$minDate->trim();
			$minDate->modify("-" . $maxDatePeriod);
		}


		# Add time if date only
		foreach($names as $key => $name) {
			if(!empty($filter[$name]) && mb_strlen($filter[$name]) < 11)
				$filter[$name] = mb_substr($filter[$name], 0, 10) . ($key == 0 ? " 00:00:00" : " 23:59:59");
		}


		# Since and till add
		try {


			# Creates new since
			if(!empty($filter[$names[0]]))
				$since = new DateTime($filter[$names[0]]);


			# Creates new till
			if(!empty($filter[$names[1]]))
				$till = new DateTime($filter[$names[1]]);


			# Check if since less than minimum
			if($since && $minDate) {
				$difference = $since->diff($minDate);
				if($difference->format("%R") == "+")
					$since = clone $minDate;
			}


			# Check if till less than minimum
			if($till && $minDate) {
				$difference = $till->diff($minDate);
				if($difference->format("%R") == "+")
					$till = clone $minDate;
			}



			# If we have max period
			if($period) {


				# If both set then we need to check
				if($since && $till) {

					# Make interval collapse to count
					$extract = clone $since;
					$extract->modify("+".$period);

					# Check difference
					if($extract < $till)
					{
						$till = clone($since);
						$till->modify("+$period");
					}

				}

			}


			# If we have default period
			if($defaultPeriod) {

				# If no date set then till is today
				if(!$since && !$till) {
					$till  = clone $today;
					$since = clone $today;
					$since->trim();
					if(!(@$since->modify("-" . $defaultPeriod)))
						throw new systemErrorException("Неверно указан defaultPeriod");

				}

				# If no since but till
				if($till && !$since) {
					$since = clone $today;
					$since->trim();
					$since->modify("-$defaultPeriod");
				}


				# Till cant be bigger than now
				if($till > $today) $till = clone($today);

			}

			# Return as array
			return array($since, $till);


		} catch (systemException $e) {
			throw $e;
		} catch (Exception $e) {
			throw new userErrorException("Неверно указана дата");
		}
	}

	/**
	 * Gets system inner counter, SMS id fo example
	 * @throws databaseException
	 * @return int
	 */
	public static function getCounter() {


		# Get connection
		$link = sky::$db->getLink();

		try {

			# Prepare transaction
			if($link->beginTransaction() === false)
				throw new databaseException("Can't start transaction");

			# Make update with select
			$link->exec("UPDATE `site_configuration` SET `value` = `value` + 1 WHERE `section` = 'sms' AND `name` = 'counter'");
			$counterData = $link->query("SELECT `value` FROM `site_configuration` WHERE `section` = 'sms' AND `name` = 'counter'");

			# Commit
			$link->commit();

			# Fetch data
			$data = $counterData->fetch(PDO::FETCH_ASSOC);
			$counterData->closeCursor();

			# Return counter with server offset
			return (int)sky::$config["server"]["id"] * 100 . $data['value'];

		} catch(PDOException $e) {
			$link->rollBack();
			throw new databaseException("Database connection error: " . $e->getMessage());
		}
	}


	/**
	 * Wraps words
	 * @param string $str
	 * @param int $width
	 * @param string $break
	 * @return string
	 */
	public static function wordWrap($str, $width = 76, $break = "<br />") {
		$return = '';
		$br_width = mb_strlen($break, 'UTF-8');
		for ($i = 0, $count = 0; $i < mb_strlen($str, 'UTF-8'); $i++, $count++) {
			if (mb_substr($str, $i, $br_width, 'UTF-8') == $break) {
				$count = 0;
				$return .= mb_substr($str, $i, $br_width, 'UTF-8');
				$i += $br_width - 1;
			}

			if ($count > $width) {
				$return .= $break;
				$count = 0;
			}

			$return .= mb_substr($str, $i, 1, 'UTF-8');
		}

		return $return;
	}

}

/* Shortcut */
class utils extends utilities {}
