<?php

/**
 * Class to work with information messages
 */
class info {

	/**
	 * Holds all messages
	 * @var array
	 */
	private static
		$messages = array(),
		$last = "";

	/**
	 * Returns last added message
	 * @return string
	 */
	public static function getLast() {
		return self::$last;
	}

	/**
	 * Return number of messages
	 * @return Int
	 */
	public static function any() {
		return (bool)sizeof(self::$messages);
	}

	/**
	 * Adds message to stack
	 * @param string $message		Message text
	 * @param string $messageName	Message name
	 * @param string $type			Message type
	 */
	public static function add($message, $messageName = "global", $type = "error") {

		# Create array
		if(!isset(self::$messages[$type]))
			self::$messages[$type] = array();

		# Create sub-array
		if(!isset(self::$messages[$type][$messageName]))
			self::$messages[$type][$messageName] = array();

		# Save last message
		self::$last = $message;

		# Add message
		self::$messages[$type][$messageName][] = $message;

	}

	/**
	 * Adds error message
	 * @param string $message		Message text
	 * @param string $messageName	Message subtype
	 */
	public static function error($message, $messageName = "global") {

		# Add notice message
		self::add($message, $messageName, "error");

	}

	/**
	 * Adds success message
	 * @param string $message		Message text
	 * @param string $messageName	Message subtype
	 */
	public static function success($message, $messageName = "global") {

		# Add notice message
		self::add($message, $messageName, "success");

	}


	/**
	 * Adds notice message
	 * @param string $message		Message text
	 * @param string $messageName	Message subtype
	 */
	public static function notice($message, $messageName = "global") {

		# Add notice message
		self::add($message, $messageName, "notice");

	}

	/**
	 * Checks if error exists
	 * @param bool|string $messageName
	 * @param string $type $type
	 * @return bool true of error exists
	 */
	public static function check($messageName = false, $type = "error") {

		# Checks
		return self::get($messageName, $type) === false;

	}

	/**
	 * Gets all messages in lines array and parameters in keys
	 * @param bool|string $type Messages type
	 * @return array
	 */
	public static function getOrdered($type = false) {


		# result holder
		$result = array();


		# If no messages
		if($type && empty(self::$messages[$type]))
			return $result;


		# If not at all
		if(!$type && empty(self::$messages))
			return $result;


		# Compile
		if($type) {
			foreach(self::$messages[$type] as $subtype => $messages) {
				foreach($messages as $message) {
					$result[] = array(
						"text"    => $message,
						"subtext" => false,
						"type"    => $type,
						"subtype" => $subtype,
					);
				}
			}
		} else {
			foreach(self::$messages as $type => $subtypes) {
				foreach($subtypes as $subtype => $messages) {
					foreach($messages as $message) {
						$result[] = array(
							"text"    => $message,
							"subtext" => false,
							"type"    => $type,
							"subtype" => $subtype,
						);
					}
				}
			}
		}


		# Return
		return $result;

	}

	/**
	 * Gets messages from stack
	 * @param bool|string $messageName     Message name
	 * @param string      $type            Type of messages
	 * @param bool        $delete          Deletes message after get
	 * @return boolean|array
	 */
	public static function get($messageName = false, $type = "error", $delete = false) {

		# Return value
		$return = false;

		# If messages exists we return them
		if(is_string($messageName) && isset(self::$messages[$type][$messageName])) {

			# Get elements
			$return = self::$messages[$type][$messageName];

			# Delete if needed
			if($delete) unset(self::$messages[$type][$messageName]);

		} elseif($messageName === false && !empty(self::$messages[$type])) {

			$return = array();

			# Go though
			foreach(self::$messages[$type] as $key => $messages) {

				# Add messages to result
				$return = array_merge($return, $messages);

				# Delete if needed
				if($delete) unset(self::$messages[$type][$key]);

			}
		}

		# Return false if no messages
		return $return;

	}


}
