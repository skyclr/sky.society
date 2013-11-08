<?php

/**
 * Class request
 * Used for request routing and etc.
 */
class request {

	public static $real = false;

	/**
	 * Gets path with resolved .. and .
	 * @param string $default Default page name, if none in address
	 * @return string Path
	 */
	public static function getPath($default = "index") {


		# Get address elements
		$route = self::getAddress($default);


		# Make compiled
		return implode("/", $route);

	}

	/**
	 * Gets current addresses stack
	 * @param string $default Default page
	 * @return array
	 */
	public static function getAddress($default = "index") {


		# If no sub pages were requested
		if(empty($_SERVER["REDIRECT_REQUEST_PATH"]) && empty($_SERVER["REQUEST_PATH"]))
			self::setAddress($default);


		# Return slitted path
		if(!empty($_SERVER["REQUEST_PATH"]))
			$elements = explode("/", $_SERVER["REQUEST_PATH"]);
		else
			$elements = explode("/", $_SERVER["REDIRECT_REQUEST_PATH"]);


		# Save real route parts
		if(!self::$real)
			self::$real = $elements;


		# Would hold route parts
		$route = array();


		# Compile route
		foreach($elements as $element) {

			# Skip empty
			if($element == "")
				continue;

			# If go back
			if(trim($element) == ".."){
				if(sizeof($route))
					array_pop($route);
				# If not same
			} else {
				if(trim($element) != ".")
					array_push($route, $element);
			}

		}


		/* If synonym */
		if(implode("/", $route) != ($newRoute = self::findSynonyms(implode("/", $route)))) {
			self::setAddress($newRoute);
			return self::getAddress($default);
		}


		# Return
		return $route;

	}

	/**
	 * Sets current address
	 * @param string $address Correct address
	 */
	public static function setAddress($address) {
		$_SERVER["REDIRECT_REQUEST_PATH"] = $address;
	}

	/**
	 * Gets page name from path
	 * @param string $default Default page in no path gained
	 * @return string
	 */
	public static function getPageName($default = "index") {

		# Get path
		$pathElements = self::getAddress($default);

		# Return path last element
		return array_pop($pathElements);

	}

	/**
	 * Finds page synonyms
	 * @param string $page Page name
	 * @throws systemErrorException
	 * @return string Page name, as is fi no synonyms or synonym page
	 */
	public static function findSynonyms($page) {


		# If none
		if(empty(sky::$config["pages"]["synonyms"]))
			return $page;


		# Search through
		foreach(sky::$config["pages"]["synonyms"] as $synonym => $original) {

			/* If we found it */
			if($find = preg_match("/^$synonym$/", $page))
				return preg_replace("/^$synonym$/", $original, $page);

			/* If regexp wrong */
			if($find === false)
				throw new systemErrorException("Wrong synonym set: '$synonym' preg failed");

		}


		# Return
		return $page;

	}

}