<?php

/**
 * Sends error back data
 * @param   array|string $params What to send backs
 * @param string|bool    $name   Name of return element to set params to
 */
function jError($params, $name = false) {

	# Converting
	if(is_string($params) && !$name)
		$name = "text";

	# Set ass array part
	if($name)
		$params = array($name => $params);

	# Sending
	die(json_encode(array_merge(array("error" => true), $params)));

}

/**
 * Sends success back data
 * @param   array|string $params What to send backs
 * @param string|bool    $name   Name of return element to set params to
 */
function jSend($params, $name = false) {

	# Converting
	if(is_string($params) && !$name)
		$name = "text";

	# Set ass array part
	if($name)
		$params = array($name => $params);

	# Sending
	die(json_encode(array_merge(array("error" => false), $params)));

}

# Headers
header('Content-type: application/json');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 	// Date in the past to disable cache


try {

	//sleep(1);

	# No exceptions to output
	# sky::$config["development"]["traceExceptions"] = false;


	# Operation subtype
	$type = vars::type();


	# Login check
	if(!auth::isLoggedIn())
		throw new userErrorException('Для выполнения операции вы должны быть <a href="">авторизованы</a> в системе.');


	# Set section
	if(sizeof(request::$real) > 1)
		$_GET["json"] = request::$real[1];


	# Make operation according parameter
	switch(vars::get("json", "trim", "always")) {

		# Folders operations
		case "folders":
			require_once "ajax/folders.php";
			break;

		# Files operations
		case "files":
			require_once "ajax/files.php";
			break;

		# Comments operations
		case "comments":
			require_once "ajax/comments.php";
			break;

		# Comments operations
		case "messages":
			require_once "ajax/messages.php";
			break;

		# If no proper type
		default: throw new userErrorException("Не указан тип выполняемой операции");

	}

}
# Database errors
catch(databaseException $e) {
	jError("Во время обращения к базе данных произошла ошибка");
}
# Operations error
catch(userException $e) {
	jError($e->getMessage());
}
# Operations error
catch(systemException $e) {
	jError("Во время выполнения операции произошла ошибка");
}