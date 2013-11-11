<?php

# User controller class
include_once 'userController.php';

/**
 * Class for user authentication
 */
class auth {

	/**
	 * User data variable
	 * @var userController
	 */
	public static $me = false;

	/**
	 * Default preferences variables
	 */
	private static
		$defaultPreferences = array(),
		$isInit     		= false;

	/**
	 * Users table name
	 * @var string
	 */
	public static
		$usersTable	= "users";

	/**
	 * This function initialise all authentication instructions
	 */
	function __construct() {


		# Logout if type
		if(vars::type() == 'logout')
			self::logout();


		# Try to login if type
		if(vars::type() == 'login') {

			# Try to authenticate user
			if($user = self::authentication()) {

				# If user correct then we authorise him
				self::authorisation($user, vars::post("autologin", "bool", "always"));

				# After login redirect
				if(!is_null($redirect = validator::single(sky::$config["authenticate"], "redirect", "trim")))
					sky::goToPage($redirect);

			}
			else info::error("Неверная пара логин/пароль" . $user);

		}


		# Maybe user loggedIn before
		if(!self::isLoggedIn())
			self::isLoggedInBefore();


		# Create user controller
		if(self::isLoggedIn()) {
			self::$me = new userController($_SESSION);
			if(sky::$config["authenticate"]["preferences"])
				self::$me->setPreferences($_SESSION['preferences']);
			return;
		}


		# Login guest
		self::loginGuest();

	}

	/**
	 * Login guest account if it persists in preferences
	 */
	static function loginGuest() {


		# If no need in guest account
		if(empty(sky::$config["authenticate"]["guest"]))
			return;


		# Login guest
		self::$me = new userController(sky::$config["authenticate"]["guest"]);


		# Set guest preferences
		if(!empty(sky::$config["authenticate"]["preferences"]) && self::$defaultPreferences) {

			# Set if none
			if(empty($_SESSION['preferences']))
				$_SESSION['preferences'] = self::$defaultPreferences;

			# Add to controller
			Auth::$me->setPreferences($_SESSION['preferences']);
		}

	}

	/**
	 * Initialization of parameters
	 * @param string $usersTableAddress        Name of tables where stores user data
	 * @param array  $defaultPreferences      Array of default settings which will assign to new users,
	 *                                        or if any error on get user setting occupied
	 * @throws systemErrorException
	 */
	static function initialization($usersTableAddress, $defaultPreferences) {


		# Check
		validator::value($usersTableAddress, "trim", "Bad users table address");


		# Set locals
		self::$usersTable = $usersTableAddress;
		self::$isInit	  = true;


		# Check preferences
		if(empty($defaultPreferences) || !is_array($defaultPreferences))
			return;


		# Set local
		self::$defaultPreferences = $defaultPreferences;


		# Default preferences set
		if(empty($_SESSION["preferences"]))
			$_SESSION["preferences"] = $defaultPreferences;

	}

	/**
	 * This function performs user logout
	 * @param Boolean $redirect If TRUE after login page will be redirected to root
	 */
	static function logout($redirect = true) {


		# Unset php cookies data
		if(isset($_COOKIE['sessionId'])) unset($_COOKIE['sessionId']);
		if(isset($_COOKIE['username']))  unset($_COOKIE['username']);


		# Destroys session
		session_unset();
		session_destroy();


		# Unset user cookies
		setcookie('sessionId', '', time() - 3600, sky::$config["authenticate"]["domain"]);
		setcookie('autoLogin', '', time() - 3600, sky::$config["authenticate"]["domain"]);
		setcookie('username' , '', time() - 3600, sky::$config["authenticate"]["domain"]);


		# Page redirect
		if($redirect)
			sky::goToPage("Location: ./");

	}

	/**
	 * This function performs user authorisation on server based on authentication result.
	 * @param Array|bool $user      User information gathered by Auth::authentication.
	 * @param bool       $autoLogin Indicates that we should login user bu cookie later
	 * @throws systemErrorException
	 * @throws databaseException
	 */
	static function authorisation($user = false, $autoLogin = false) {

		# Check if initialised
		if(!self::$isInit)
			throw new systemErrorException("Auth options not initialized");


		# Write all its data to session
		if($user)
			$_SESSION = $user;


		# Set loggedIn key
		$_SESSION['loggedIn'] = true;


		# Load preferences
		try {

			# Save cookies for autoLogin
			if($autoLogin) {

				# Generation of unique ID
				$uniqueId = md5(date("U").rand(1000, getrandmax()));


				# Cookies set
				setcookie('sessionId', $uniqueId		, time() + 60 * 60 * 24 * 30, sky::$config["authenticate"]["domain"]);
				setcookie('autoLogin', 'true'			, time() + 60 * 60 * 24 * 30, sky::$config["authenticate"]["domain"]);
				setcookie('username' , $user['username'], time() + 60 * 60 * 24 * 30, sky::$config["authenticate"]["domain"]);


				# Update database
				sky::$db->make(self::$usersTable)->where($user['id'])->set("date", date(DB2::DATETIME_SQL))->set("sessionId", $uniqueId)->update();

			}


			# If we don't use preferences
			if(!sky::$config["authenticate"]["preferences"])
				return;


			# Get user preferences
			$preferences = sky::$db->make(sky::$config["authenticate"]["preferences"])->where($user["id"], "owner")->get("single");


			# Save all preferences to storage
			if($preferences)
				$_SESSION['preferences'] = $preferences;


			# Add user preferences	
			elseif(self::$defaultPreferences) {

				# Save default preferences
				sky::$db->make(sky::$config["authenticate"]["preferences"])->set(self::$defaultPreferences)->set("owner", $user["id"])->insert();

				# Save to session
				$_SESSION['preferences'] = self::$defaultPreferences;

			}

		} catch(baseException $e) {

			# In case of error
			self::logout(false);
			info::error("Ошибка во время авторизации", "error");

		}
	}

	/**
	 * This function try to authenticate user, and return user data on success
	 *
	 * @param bool|String $username  Name of user to authenticate
	 * @param bool|String $password  Password of this user
	 * @param bool|String $sessionId Session identifier, may be alternative for password
	 * @throws systemErrorException
	 * @return array|bool FALSE on fail, user data array otherwise
	 */
	static function authentication($username = false, $password = false, $sessionId = false) {

		# Check if initialised
		if (!self::$isInit)
			throw new systemErrorException("Переменные аутентификации не инициализированы");


		# Check is username set
		if($username === false && is_null($username = vars::post("username", "trim")))
			return false;


		# Checks if isset password
		if($password === false && $sessionId === false && is_null($password = vars::post("password", "trim")))
			return false;


		try {

			# Prepare request
			$request = sky::$db->make(self::$usersTable)->where("username", $username)->where("activated", 1);


			# Get user by name and password
			if($password !== false)
				$user = $request->where(array("password", "password"), $password)->get("single");

			# Get user by name and unique id
			else
				$user = $request->where("sessionId", $sessionId)->get("single");


		} catch (baseException $e) {
			info::error("Ошибка во время аутентификации пользователя");
			return false;
		}


		# If no data gathered
		if(!$user)
			return false; # If we didn't get user return false


		# Return data
		return $user;

	}

	/**
	 * This function checks cookies variables(sessionId, username) and try to authorizate his if they setted
	 * @return Boolean Returns FALSE if they not set
	 */
	static function isLoggedInBefore() {


		# If user data was saved
		if(!isset($_COOKIE['sessionId']) || !isset($_COOKIE['username']))
			return false;


		# We try to authorise user
		if($user = self::authentication(vars::cookie('username', "trim"), false, vars::cookie('sessionId'))) {
			self::authorisation($user, true);
			return true;
		}


		# False if auth failed
		return false;

	}

	/**
	 * Checks if user currently loggedIn
	 * @return Boolean Returns TRUE if user logged and FALSE otherwise
	 */
	static function isLoggedIn() {

		# If properly logged in
		return !empty($_SESSION["loggedIn"]);

	}

}