<?php

/**
 * Global system preferences
 */
$preferences = array(

	/**
	 * Development parameters
	 */
	"development" => array(
		"traceExceptions" => "screen", # Outputs all exceptions on screen
		"noTwigCache" => true
	),

	/**
	 * Main site preferences
	 */
	"site" => array(
		"name" 	=> "SKY.society",
		"base"	=> "/society/sky.society/html/"
	),

	/**
	 * Database connection parameters
	 * if FALSE the not used
	 */
	"database" => array(
		"use"	   => false, # Indicates that we shouldn't use it
		"host"     => "", 	 # Host address
		"name"     => "", 	 # Database name
		"port"	   => "", 	 # Port
		"user"     => "", 	 # Username
		"password" => "", 	 # Password
		"charset"  => "UTF8" # Default charset
	),

	/**
	 * Authenticate preferences
	 * If FALSE then not used
	 */
	"authenticate" => array(
		"table"		=> "",			# Table that holds user data
		"domain"	=> "",			# Domain name to save cookie auth for
		"redirect"	=> false,		# Where to redirect after login, false if no redirect needed
		"guest"		=> array(		# Guest account parameters
			"username" => "Гость"
		),
		"preferences" => array()	# Preferences for guests and new users
	),

	/**
	 * System pages names
	 */
	"systemPages" => array(
		"errorGlobal" => "errorGlobal",	# Global error page location
		"errorPage"	  => "errorPage"	# Error during page render path
	),

	/**
	 * SMTP mail properties
	 */
	"smtp" => array(
		"host"      => "",
		"server"    => "",				# Server IP or domain
		"port"      => 465,				# Server port
		"login"     => "",				# User
		"password"  => "",				# Password
		"ehlo"      => "EHLO localhost",# Greetings response parameters
		"newline"   => '\r\n',			# Ne line separator
		"ssl"		=> true				# Is SSL needed
	),
);

/**
 * Holds project locations
 */
require_once "locations.php";

/**
 * Holds templates information
 */
require_once "templates.php";