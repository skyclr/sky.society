<?php

new content();

/**
 * Class content
 * Content generate class
 */
class content {

	public static

		/**
		 * Rendered page
		 * @var string
		 */
		$renderedPage = "",

		/**
		 * Path to current page
		 * @var string
		 */
		$pagePath = "/index",

		/**
		 * Page name
		 * @var string
		 */
		$pageName = "index",

		/**
		 * Page object
		 * @var bool|basePage
		 */
		$page = false,

		/**
		 * List of pages available without auth
		 * @var array
		 */
		$noAuthPages = array("login", "registration", "ajax", "password", "test");

	/**
	 * Make page and resolve addresses
	 */
	public function __construct() {


		# Make twig extend
		twig::init();


		# Get request
		$path = request::getPath(self::$pageName);


		# Get page name
		self::$pageName = request::getPageName(self::$pageName);


		# Check if available
		if(!empty(sky::$config["authenticate"]["use"]) && !auth::isLoggedIn() && !in_array($path, self::$noAuthPages))
			sky::goToPage("./" . self::$noAuthPages[0]);


		# Make page
		self::makePage($path);


		# If no page created
		if(!self::$page) {

			# Set code
			header("HTTP/1.0 404 Not Found", true, 404);

			# Render 404 page
			self::$renderedPage = sky::$twig->render("/system/404.twig", array("page" => $path));

		}

	}

	/**
	 * Makes new page
	 * @param string $pagePath Page path inside pages folder
	 */
	public static function makePage($pagePath = "index") {


		# Save page path
		self::$pagePath = $pagePath;


		# Page object creation
		try {


			# Page class path
			$classPath = sky::location("pages") . self::$pagePath . ".php";


			# Existing check
			if(!file_exists($classPath))
				return;


			# Page include
			/** @noinspection PhpIncludeInspection */
			include $classPath;


			# Check if proper class exists
			if(!class_exists("page")) {
				self::$page = true;
				return;
			}


			# Create page object
			self::$page = basePage::baseInit();


			$jsTemplates = array();
			foreach(self::$page->jsTemplates as $template) {
				$jsTemplates[] = array(
					"path" => $template["path"],
					"date" => $template["date"]
				);
			}


			# Render
			self::$renderedPage = sky::$twig->render("/shared/".self::$page->parentTemplate.".twig", array(
				"page"        => self::$page,
				"pageName"    => self::$pageName,
				"pagePath"    => self::$pagePath,
				"realPath"	  => request::getPath(""),
				"jsTemplates" => $jsTemplates ? json_encode($jsTemplates, true) : "{}"
			));


		} catch(Exception $e) {


			# Log if needed
			if(!($e instanceof baseException))
				baseException::log($e->getMessage());


			# Message
			self::$renderedPage = sky::$twig->render("/system/errorPage.twig", array("error" => "Во время работы произошла ошибка (" . $e->getMessage() . "), пожалуйста попробуйте позже"));


			# Mark that page was rendered
			self::$page = true;

		}

	}

}