<?php

/**
 * Main class
 */ 
class sky {

	/**
	 * Predefine constants
	 */
	const TIME_DATE 	= "H:i d.m.Y";
	const DATE_TIME		= "d.m.Y H:i";
	const DATE_ONLY 	= "d.m";
	const DATE_FULL		= "d.m.Y";
	const SKY_VERSION	= "3.0";
    
	/**
	 * Database access class 
	 * @var DB2
	 */
    public static $db;

	/**
	 * Self link
	 * @var bool|sky
	 */
	private static $sky	= false;
	
	/**
	 * System preferences
	 * @var Array
	 */
	public static $config;

	/**
	 * Twig environment object, used to render templates and etc
	 * @see http://twig.sensiolabs.org/documentation
	 * @var Twig_Environment
	 */
	public static $twig = false;

	/**
	 * Twig loader
	 * @var Twig_Loader_Filesystem
	 */
	public static $twigLoader;

	/**
	 * Automatically load classes
	 * @param $className
	 */
	public function autoLoad($className) {
		if(file_exists(sky::location("classes") . $className .'.php')) {
			include sky::location("classes") . $className .'.php';
		}
	}

	/**
	 * Library every page init
	 * @param bool|string $type Type of preformed actions on init
	 * @global     $libraryPreferences
	 */
	public function __construct($type = false) {


		# Preferences
		global $preferences;


		# Save preferences
		self::$config = $preferences;


		# Starting of session
		if($type !== "console")
			session_start();


		try {

			# File auto loader
			spl_autoload_register(array($this, 'autoLoad'));


			# Add library path
			self::$config["locations"]["library"] = realpath(dirname(__FILE__))."/";


			# Try to init
			try {

				# Init
				$this->init();

			} catch(userErrorException $e) {}


			# Self link
			self::$sky = $this;


			# No render and page creation for console
			if($type == "console") return;


			# Content include after all initialisations
			require_once self::location("contentClass");


			#Output rendered page
			echo content::$renderedPage;

			# No more actions
			return;

		}
		catch(databaseException $e) {

			# User info error message
			$error = "В данный момент мы меняем конфигурацию базы данных. Пожалуйста попробуйте позже.";
		}
		catch(baseException $e) {

			# Error
			$error = "Во время работы произоша системная ошибка";

		}
		catch(Exception $e) {

			# Log
			baseException::log($e->getMessage(), "error");

			# Error
			$error = "Во время работы произоша системная ошибка";

		}


		# Render if error occupied
		if(self::$twig)
			# If twig init
		self::$twig->display("/system/errorGlobal.twig", array("error" => $error));
		else
			# If no twig
			die("<h4>$error</h4>");

	}

	/**
	 * Initialise base classes and variables
	 * @param bool|string $type Type of initialisation
	 */
	private function init($type = false) {


		# If we use twig templates
		if(!empty(self::$config["templates"]))
			$this->initTwig();


		# SQL and authentication initialization
		if(!empty(self::$config["database"]) && (!isset(self::$config["database"]["use"]) || self::$config["database"]["use"] !== false)) {


			# Init database connection
			self::$db = new DB2(
				self::$config["database"]["host"],
				self::$config["database"]["name"],
				self::$config["database"]["user"],
				self::$config["database"]["password"]);


			# Init authentication
			if($type !== "console" && !empty(self::$config["authenticate"])  && (!isset(self::$config["authenticate"]["use"]) || self::$config["authenticate"]["use"] !== false)) {
				auth::initialization(
							self::$config['authenticate']["table"],
							self::$config['authenticate']["preferences"]);

				new auth();
			}
		}


    }

	/**
	 * Init twig template engine
	 */
	private function initTwig() {

		# Pear packages
		require_once('external/PEAR.php');
		require_once('external/Twig/Autoloader.php');


		# Register twig
		Twig_Autoloader::register();


		# Create loader
		self::$twigLoader = new Twig_Loader_Filesystem(self::location("templates"));


		# Create environment
		self::$twig = new Twig_Environment(self::$twigLoader, array('cache' => self::location("twigCache")));


		# Disable cache if needed
		if(!empty(self::$config['templates']['noCache']) || !empty(self::$config["development"]['noTwigCache']))
			self::$twig->setCache(false);


	}

	/**
	 * Gets location path from preferences
	 * @param string $name Name of location
	 * @throws systemErrorException
	 * @return string
	 */
    public static function location($name) {


		# Check if location exists
		if(!isset(self::$config["locations"][$name])) {


			# Replacement paths
			if($name === "contentClass")
				return self::location("classes") . "content.php";


			# Else exception go
			throw new systemErrorException("Unknown location requested: " . $name);

		}


		# Return path
		return self::$config["locations"][$name];
    	
    }
    
    /**
     * Redirects to page
     * @param string $page Page URL
     */
    public static function goToPage($page) {
        header("Location: " . self::$config["site"]["base"] . $page, true, 301);
        die('Похоже ваш браузер не поддерживает перенаправления, перейдите на <a href="' . self::$config["site"]["base"] . $page . '">эту страницу</a>');
    }

}