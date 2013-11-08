<?php

/**
 * Class twig
 * Works with twig template engine
 */
class twig {

	/**
	 * Init twig extensions
	 * @param bool|string|array $parameters Special init parameters
	 * @throws systemErrorException
	 */
	public static function init($parameters = false) {


		# If not created
		if(!sky::$twig)
			throw new systemErrorException("Try to init Twig without using it");


		# Add filter
		sky::$twig->addFilter(new Twig_SimpleFilter("selected", array("twig", "filterSelected"), array('is_safe' => array('html'))));
		sky::$twig->addFilter(new Twig_SimpleFilter("checked",	array("twig", "filterChecked"),  array('is_safe' => array('html'))));
		sky::$twig->addFilter(new Twig_SimpleFilter("disabled", array("twig", "filterDisabled"), array('is_safe' => array('html'))));


		# Make globals
		sky::$twig->addGlobal('me'			, auth::$me);
		sky::$twig->addGlobal('post'		, $_POST);
		sky::$twig->addGlobal('get'			, $_GET);
		sky::$twig->addGlobal('preferences'	, sky::$config);
		sky::$twig->addGlobal('base'		, sky::$config['site']['base']);


	}

	public static function filterSelected($expression) {
		return $expression ? 'selected="selected"' : "";
	}
	public static function filterChecked($expression) {
		return $expression ? 'checked="checked"' : "";
	}
	public static function filterDisabled($expression) {
		return $expression ? 'disabled="disabled"' : "";
	}

}