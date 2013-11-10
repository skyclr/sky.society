<?php

/**
 * Twig template preferences
 * If FALSE then not used
 * @see http://twig.sensiolabs.org
 */
$preferences["templates"] = array(

	/**
	 * Pages location inside templates
	 */
	"pages" 	=> "pages/",

	/**
	 * If true no Twig cache would be used
	 */
	"noCache" 	=> true,

	/**
	 * JS templates preferences
	 */
	"jsTemplates" => array(

		/**
		 * JS templates that would be included in all pages
		 * @type {array|boolean}
		 */
		"default" => array("forms"),

		/**
		 * JS templates extension
		 * @type {string}
		 */
		"extension" => "hbs",

	)
);