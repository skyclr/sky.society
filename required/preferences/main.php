<?php

/**
 * Global system preferences
 */
$preferences = array(

	/**
	 * Development parameters
	 */
	"development"  => array(
		"traceExceptions" => "screen", # Outputs all exceptions on screen
		"noTwigCache"     => true
	),

	/**
	 *
	 */
	"preferences" => array(
		"external" => "preferences" # table with external preferences
	),

	/**
	 * Main site preferences
	 */
	"site"         => array(
		"name" => "sky.cloud",
		"base" => "/"
	),

	/**
	 * Database connection parameters
	 * if FALSE the not used
	 */
	"database"     => array(
		"use"      => true,         # Indicates that we shouldn't use it
		"host"     => "127.0.0.1",  # Host address
		"name"     => "society",    # Database name
		"port"     => "3306",       # Port
		"user"     => "root",       # Username
		"password" => "150287",     # Password
		"charset"  => "UTF8"        # Default charset
	),

	/**
	 * Authenticate preferences
	 * If FALSE then not used
	 */
	"authenticate" => array(
		"use"         => true,      # Indicates that we shouldn't use it
		"table"       => "users",   # Table that holds user data
		"domain"      => "/",       # Domain name to save cookie auth for
		"redirect"    => false,     # Where to redirect after login, false if no redirect needed
		"guest"       => array(     # Guest account parameters
			"username" => "Гость"
		),
		"changeable" => array(
			"hasAvatar",
			"avatarExtension"
		),
		"preferencesTable" => "userSettings",
		"preferences" => array(
			"perPage" => 30
		)    # Preferences for guests and new users
	),

	/**
	 * System pages names
	 */
	"systemPages"  => array(
		"errorGlobal" => "errorGlobal", # Global error page location
		"errorPage"   => "errorPage"    # Error during page render path
	),

	/**
	 * SMTP mail properties
	 */
	"smtp"         => array(
		"host"     => "",
		"server"   => "",               # Server IP or domain
		"port"     => 465,              # Server port
		"login"    => "",               # User
		"password" => "",               # Password
		"ehlo"     => "EHLO localhost", # Greetings response parameters
		"newline"  => '\r\n',           # Ne line separator
		"ssl"      => true              # Is SSL needed
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

/**
 * Holds pages information
 */
require_once "pages.php";