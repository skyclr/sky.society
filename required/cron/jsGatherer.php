<?php

	# Get file content
	function getFileContent($file) {
		echo file_get_contents($file);
		echo "\n\n";
	};

	$order = array();

	# Get file content
	function readDirectory($directory) {

		# Globals
		global $preferences, $order;

		# Get directory
		$path = substr($directory, strlen($preferences["locations"]["javascript"]));

		# Skip pages
		if($path == "pages/" || $path == "lib/mvc/") return;

		# Directories list
		$files = glob($directory . "*");

		# Reorder
		if(!empty($path) && array_key_exists($path, $order)) {
			foreach($order[$path] as $file) {
				array_splice($files, array_search($directory.$file, $files), 1);
				array_unshift($files, $directory.$file);
			}
		}

		# Go through
		foreach($files as $file) {

			# Log
			echo "//Path: " . $file. "\n";

			# Skip gathered
			if(basename($file) == "gathered.js")
				continue;

			# Read
			if(is_dir($file))
				readDirectory($file . "/");
			else
				getFileContent($file);

		}

	};

	# Get preferences
	require_once dirname(__FILE__). "/../preferences/main.php";

	# Buffering
	ob_start();

	# Read main directory
	readDirectory($preferences["locations"]["javascript"]);

	$content = ob_get_contents();
	ob_end_clean();
	file_put_contents($preferences["locations"]["javascript"] . "gathered.js", $content);
	echo "Javascript compiled to gathered.js ".@date("– Y-m-d H:i")."\n";