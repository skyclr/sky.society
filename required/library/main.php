<?php 

# Set preferences
date_default_timezone_set('Europe/Moscow');
ini_set("max_execution_time", 15);
mb_internal_encoding("utf-8");
error_reporting("E_ALL");

# Include preferences
require realpath(dirname(__FILE__)."/../preferences/main.php");

# Path to include files
set_include_path(dirname(__FILE__));

# Classes
require_once "sky.php";
require_once "authentication.php";
require_once "database2.php";
require_once "datetime.php";
require_once "exceptions.php";
require_once "files.php";
require_once "info.php";
require_once "images.php";
require_once "network.php";
require_once "request.php";
require_once "utilities.php";
require_once "validator.php";
require_once "vars.php";


