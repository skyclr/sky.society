<?php

/**
 * Class validator used to validate user or data
 */
class validator {

	/**
	 * Available type of values
	 * @var array
	 */
	private $types = array(
		"trim",
		"positive",
		"natural",
		"numeric",
		"email",
		"unsafe",
		"bool"
	);

	/**
	 * Available rules types
	 * @var array
	 */
	private $rulesTypes = array(
		"same"		=> "string",
		"maxLength" => "natural",
		"minLength" => "natural",
		"min"		=> "natural",
		"max"		=> "natural",
		"required"  => "boolean",
		"preg"		=> "string",
	);

	/**
	 * Available return actions on error
	 * @var array
	 */
	private $returns = array(
		"null",
		"always",
		"exception",
		"system",
		"notice",
		"error",
		"success"
	);

	/**
	 * Would hold data that pass validation
	 * @var array
	 */
	private $result = array();

	/**
	 * Data that should to be validated
	 * @var mixed
	 */
	private $data = false;

	/**
	 * Rules list
	 * @var array
	 */
	private $rules = array();

	/**
	 * Error texts
	 * @var array
	 */
	private $errors = array();

	/**
	 * Creates new
	 */
	function __construct($data) {
		$this->data = $data;
	}

	/**
	 * Creates new validator
	 * @param $data
	 * @return validator
	 */
	public static function init($data) {
		return new self($data);
	}

	/**
	 * Validates one value
	 * @param string|boolean $value     Contains key of field that should be checked
	 * @param string|array   $ruleName  Name of rule that should to be applied
	 * @param string|boolean $rule      Rule condition
	 * @param string         $return	 What to return or what error make
	 * @param string|boolean $errorText Text for userErrorException
	 * @return mixed
	 */
	public static function value($value, $ruleName, $rule = false, $return = "null", $errorText = false) {
		return validator::init($value)->rule(false, $ruleName, $rule, $return, $errorText)->get();
	}

	/**
	 * Validates one value
	 * @param string|boolean $data       Contains data should be checked
	 * @param string|integer $key        Key from data
	 * @param string|array   $ruleName   Name of rule that should to be applied
	 * @param string|boolean $rule       Rule condition
	 * @param string         $return	 What to return or what error make
	 * @param string|boolean $errorText  Text for userErrorException
	 * @return mixed
	 */
	public static function single($data, $key, $ruleName, $rule = false, $return = "null", $errorText = false) {
		 $result = validator::init($data)->rule($key, $ruleName, $rule, $return, $errorText)->get();
		return $result[$key];
	}

	/**
	 * Gets converted result
	 * @return array
	 */
	public function get() {
		return $this->result;
	}

	/**
	 * Creates new rule
	 * @param string|boolean $key       Contains key of field that should be checked
	 * @param string|array   $ruleName  Name of rule that should to be applied
	 * @param string|boolean $rule      Rule condition
	 * @param string         $return	What to return on error
	 * @param string|boolean $errorText Text for userErrorException
	 * @return $this
	 */
	public function rule($key, $ruleName, $rule = false, $return = "null", $errorText = false) {


		# If array given
		if(is_array($ruleName)) {

			# Go through
			foreach($ruleName as $name => $value) {
				if(is_numeric($name)) $name = "required";
				$this->rule($key, $name, $value, $errorText);
			}

			# Self return
			return $this;

		}


		# Shifting
		if(!in_array($ruleName, array_keys($this->rulesTypes)))			{ $errorText = $return; $return = $rule; $rule = $ruleName; $ruleName = "required"; }
		if($ruleName == "required" && !in_array($rule, $this->types)) 	{ $errorText = $return; $return = $rule; $rule = true; }
		if($return && !in_array($return, $this->returns))				{ $errorText = $return; $return = "exception"; }



		# Set array
		if(empty($this->rules[$key]))
			$this->rules[$key] = array();


		# Save rule
		$this->rules[$key][$ruleName] = $rule;


		# If we have error text
		if($errorText)
			$this->setError($key, $errorText, $ruleName);


		# Validate
		$this->validate($key, $ruleName, $rule, $return);


		# Self return
		return $this;

	}

	/**
	 * Writes value to result
	 * @param string|boolean $key       Contains key of field that should be checked
	 * @param Mixed          $value		Data to be written
	 * @return Mixed
	 */
	public function writeResult($key, $value) {

		# If no key
		if($key === false)
			return $this->result = $value;


		# If not array
		if(!mb_strstr($key, "."))
			return $this->result[$key] = $value;


		# Split keys
		$key = mb_split(".", $key);


		# Start point
		$parent = $this->data;


		# Go through keys
		foreach($key as $i => $part) {

			# If no such key
			if(!isset($parent[$part]))
				$parent[$part] = array();

			# If at final
			if($i == sizeof($key) - 1)
				$parent[$part] = $value;

		}

		# Write
		return $value;

	}

	/**
	 * Sets error text for specified
	 * @param string|boolean $key Name of part of data that should be validated
	 * @param string $text Error text for userErrorException
	 * @param string $rule Rule identifier
	 * @return $this
	 */
	public function setError($key, $text, $rule = 'all') {


		# Create array of none
		if(empty($this->errors[$key]))
			$this->errors[$key] = array();


		# Set error text
		$this->errors[$key][$rule] = $text;


		# Self return
		return $this;

	}

	/**
	 * @param $key
	 * @param $value
	 * @param $ruleName
	 * @param $rule
	 * @return int|string
	 * @throws userErrorException
	 * @throws systemErrorException
	 */
	private function validateRule($key, $value, $ruleName, $rule) {


		# Validation error flag
		$error = false;


		# If array
		if(is_array($value)) {

			foreach($value as $subValue) {
				list($value, $subError) = $this->validateRule($key, $subValue, $ruleName, $rule);
				if($subError) $error = true;
			}

			# Return
			return array($value, $error);

		}


		#  Apply rule
		switch($ruleName) {
			case "same": {
				if($value != $this->getByKey($rule))
					$error = true;
				break;
			}
			case "max": {
				if(!is_numeric($value) || $value > $rule) {
					$value = $rule;
					$error = true;
				}
				break;
			}
			case "min": {
				if(!is_numeric($value) || $value < $rule) {
					$value = $rule;
					$error = true;
				}
				break;
			}
			case "maxLength": {
				if(mb_strlen($value) > $rule) {
					$value = mb_substr($value, 0, $rule);
					$error = true;
				}
				break;
			}
			case "minLength": {
				if(mb_strlen($value) < $rule)
					$error = true;
				break;
			}
			case "required": {
				list($value, $error) = $this->validateType($value, $rule);
				break;
			}
			case "preg": {
				if(!preg_match($rule, $value))
					$error = true;
				break;
			}
			default:
				throw new systemErrorException("Unknown validation rule: $rule");
		}


		# Return
		return array($value, $error);

	}

	/**
	 * Checks if value has proper type
	 * @param $value
	 * @param $type
	 * @return int|string
	 * @throws userErrorException
	 * @throws systemErrorException
	 */
	private function validateType($value, $type) {


		# Validation error flag
		$error = false;


		#  Apply rule
		switch($type) {
			case "email": {

				# Check
				if(!(bool)preg_match('/^[a-z0-9.+-_]+@([a-z0-9-]+(.[a-z0-9-]+)+)$/i', $value))
					$error = true;

				break;

			}
			case "trim": {

				# Convert
				$value = trim($value);

				# Error
				if(!is_string($value) || empty($value))
					$error = true;

				break;

			}
			case "natural": {

				# Error
				if(!is_numeric($value) || $value < 0)
					$error = true;

				# Convert
				$value = (int)$value;

				break;

			}
			case "positive": {

				# Error
				if(!is_numeric($value) || $value < 1) {
					$value = 1;
					$error = true;
				}

				break;

			}
			case "numeric": {

				# Error
				if(!is_numeric($value)) {
					$value = 0;
					$error = true;
				}

				break;

			}
			case "bool": {

				if(empty($value) || ($value !== "false" && $value !== "true" && !is_bool($value)))
					$error = true;
				elseif($value === "false" || $value === false)
					$value = false;
				else
					$value = true;

				break;
			}
			case "unsafe": {

				if(empty($value))
					$error = true;

				break;
			}
			default: throw new systemErrorException("Unknown require type: $type");
		}


		# Removes slashes if needed
		if(get_magic_quotes_gpc())
			$value = stripslashes($value);


		# Special chars
		if($type !== "unsafe")
			$value = htmlspecialchars($value);


		return array($value, $error);


	}

	/**
	 * Gets data part by key
	 * @param string|boolean $key Data part key
	 * @return mixed|null if none
	 */
	private function getByKey($key) {


		# If no key than it data itself
		if($key == false)
			return $this->data;


		# If no spit needed
		if(!mb_strstr($key, ".")) {
			if(isset($this->data[$key])) return $this->data[$key];
			else return null;
		}


		# Split keys
		$key = mb_split(".", $key);


		# Start point
		$value = $this->data;


		# Go through keys
		foreach($key as $part) {

			# If no such key
			if(!isset($value[$part])) return
				null;

			# Go toi next step
			$value = $value[$part];

		}

		# Return
		return $value;

	}

	/**
	 * Validates one key
	 * @param string|boolean $key       Contains key of field that should be checked
	 * @param string|array   $ruleName  Name of rule that should to be applied
	 * @param string|boolean $rule      Rule condition
	 * @param string         $return    What to return on error
	 * @throws userErrorException
	 * @throws systemErrorException
	 * @return int|mixed|null|string
	 */
	private function validate($key, $ruleName, $rule, $return) {


		# Get Value to be validated
		$value = $this->getByKey($key);


		# Rules check
		if(!empty($this->rules[$key][$ruleName])) {

			# Validate
			list($value, $error) = $this->validateRule($key, $value, $ruleName, $rule, $return);

			# Default error text
			$errorText = "Value didn't pass validation";

			# If not valid
			if($error) {

				if(isset($this->errors[$key]['all']))
					$errorText = $this->errors[$key]['all'];

				# Throw error if needed
				if(isset($this->errors[$key][$ruleName]))
					$errorText = $this->errors[$key][$ruleName];

				# Value switch
				switch($return) {
					case "success":
						info::success($errorText);
						break;
					case "notice":
						info::notice($errorText);
						break;
					case "error":
						info::error($errorText);
						break;
					case "exception":
						throw new userErrorException($errorText);
					case "system":
						throw new systemErrorException($errorText);
					case "null":
						$value = null;
				}

			}



			$this->writeResult($key, $value);
		}

		return $value;

	}

}