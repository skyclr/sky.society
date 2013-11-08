<?php 
	
/** Class to work with user and other variables */
class vars {
	
	/* Var types */
	private static $types = array(
		"string",	# Any string
		"trim",		# Trims string
		"numeric",	# +/- infinity
		"natural",	# 0, 1, 2, 3
		"positive",	# 1, 2, 3, 4
		"int",		# Any integer
		"integer",	# Alias to previous
		"bool",		# True / false
		"isset"		# If var set
	);

	/**
	 * Reorders array by speciafied field, uses it values in keys
	 * @param Array       $data    Array of data to sort
	 * @param String      $name    Name of keys to sort by values of
	 * @param bool|String $key     Special key name which will be used in $name $return[$record[$name]][$key] = $record;
	 * @return array
	 */
	public static function reorderByKey($data, $name = "id", $key = false) {
	
		
		# Result array
		$result = array();

		
		# If no data then empty result
		if(!is_array($data)) 
			return $result;
		
		
		# Go thru data
		foreach($data as $record) {
			
			# Adds additional key
			if($key !== false) 
				$result[$record[$name]][$key] = $record[$key];
			# Or simle key ordering
			else 
				$result[$record[$name]] = $record;
			
		}
		
		
		# Return
		return $result;
		
	}

	/**
	 * Gets list of values from array of arrays
	 * @param Array  $data Array of arrays
	 * @param String $name Name in each array
	 * @param bool   $key
	 * @return Array
	 */
    public static function getByKey($data, $name = 'id', $key = false) {
    
		
		# Empty result
    	$result = array();
    	
		
    	# Go thru array
    	foreach($data as $item) {
			
			
			# If not suck key value
    		if(!isset($item[$name])) 
				continue;
			
			
			# If need order key
			if($key)	
				$result[$item[$key]] = $item[$name];
			# Simple value get otherwise
			else		
				$result[] = $item[$name];
    	}
    	
		# Return
    	return $result;
    
    }

	/**
	 * Gets data from post, shortcut for vars::fromArray($_POST, ...)
	 * @param String      $name        Index of array
	 * @param bool|String $type        Required result
	 * @param Mixed       $return      Return data
	 * @return array|bool|int|Mixed|null|string
	 */
	public static function post($name, $type = "trim", $return = "null") {
		$result = validator::init($_POST)->rule($name, "required", $type, $return)->get();
		return $result[$name];
	}

	/**
	 * Gets data from get, shortcut for vars::fromArray($_GET, ...)
	 * @param String      $name        Index of array
	 * @param bool|String $type        Required result
	 * @param Mixed       $return      Return data
	 * @return array|bool|int|Mixed|null|string
	 */
	public static function get($name, $type = "trim", $return = "null")  {
		$result = validator::init($_GET)->rule($name, "required", $type, $return)->get();
		return $result[$name];
	}

	/**
	 * Gets data from cookie, shortcut for vars::fromArray($_COOKIE, ...)
	 * @param String      $name        Index of array
	 * @param bool|String $type        Required result
	 * @param Mixed       $return      Return data
	 * @return array|bool|int|Mixed|null|string
	 */
	public static function cookie($name, $type = "trim", $return = "null")  {
		$result = validator::init($_COOKIE)->rule($name, "required", $type, $return)->get();
		return $result[$name];
	}

	/**
	 * Gets data from request, shortcut for vars::fromArray($_REQUEST, ...)
	 * @param String      $name        Index of array
	 * @param bool|String $type        Required result
	 * @param Mixed       $return      Return data
	 * @return array|bool|int|Mixed|null|string
	 */
	public static function request($name, $type = "trim", $return = "null") {
		$result = validator::init($_REQUEST)->rule($name, "required", $type, $return)->get();
		return $result[$name];
	}

	/**
	 * Returns POST or GET type variable
	 * @param array|bool $availableTypes is set search type value in this array and return false on no entry
	 * @return string|bool String of type or may return FALSE if no set or not in array
	 */
    public static function type($availableTypes = false) {

		# If no get
		if (!isset($_GET['type'])) {
			
			# The we'll search post
            if (isset($_POST['type'])) $type = $_POST['type'];
            else $type = false;
        }
        else $type = $_GET['type'];
		
		
		# If no correction
        if ($availableTypes === false) 
			return $type;
            
		
        # If AT not an array
        if (!is_array($availableTypes) && $type == $availableTypes) 
			return $type;
		
		
		# If in array
        if(in_array($type, $availableTypes)) 
			return $type;
		
		
        # If no match
        return false;
 
    }

	/**
	 * Returns POST or GET subtype variable
	 * @param Array|bool $availableTypes Array of types if not math return false
	 * @return string|bool String or false if not set or not math available types
	 */
     public static function subtype($availableTypes = false) {

		# If no get
		if (!isset($_GET['subtype'])) {
			
			# The we'll search post
            if (isset($_POST['subtype'])) $type = $_POST['subtype'];
            else $type = false;
        }
        else $type = $_GET['subtype'];
		
		
		# If no correction
        if ($availableTypes === false) 
			return $type;
            
		
        # If AT not an array
        if (!is_array($availableTypes) && $type == $availableTypes) 
			return $type;
		
		
		# If in array
        if(in_array($type, $availableTypes)) 
			return $type;
		
		
        # If no match
        return false;
    }
	
}