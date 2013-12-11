<?php

/**
 * Main project location
 */
$preferences['locations'] = array("project" => realpath(dirname(__FILE__) . "/../../") . "/");

/**
 * Contains array of  main locations paths
 */
$preferences['locations'] += array(
	"html"     => $preferences['locations']['project'] . "html/",
	"required" => $preferences['locations']['project'] . "required/"
);

/**
 * Sub paths
 */
$preferences['locations'] += array(

	# Html paths
	"css"         => $preferences['locations']['html'] . "css/",
	"img"         => $preferences['locations']['html'] . "img/",
	"javascript"  => $preferences['locations']['html'] . "jvs_old/",

	# Inner paths
	"logs"        => $preferences['locations']['required'] . "logs/",
	"preferences" => $preferences['locations']['required'] . "preferences/main.php",
	"classes"     => $preferences['locations']['required'] . "classes/",
	"pages"       => $preferences['locations']['required'] . "pages/",
	"cron"        => $preferences['locations']['required'] . "cron/",
	"library"     => $preferences['locations']['required'] . "library/",
	"external"    => $preferences['locations']['required'] . "library/external/",

	# Twig paths
	"templates"   => $preferences['locations']['required'] . "templates/",
	"twigCache"   => $preferences['locations']['required'] . "templates/cache/",
	"twigSystem"  => $preferences['locations']['required'] . "templates/system/",
	"twigJs"      => $preferences['locations']['required'] . "templates/js/",

	# Storage
	"files"       => $preferences['locations']['html'] . "files/",
	"users"       => $preferences['locations']['html'] . "files/users/",
	"temp"       => "/tmp/"

);
