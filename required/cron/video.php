<?php

/**
 * Include main library file
 */
include dirname(__FILE__)."/../library/main.php";

/**
 * New sky object performs all initializations
 */
new sky('console');


try {

	# Get videos
	$videos = sky::$db->make("files")->where("type", "video")->where("width", 0)->get();

	# If none
	if(!$videos)
		die("No videos");

	# Go through
	foreach($videos as $video) {

		# Find extension
		preg_match('/(\d{2,5})x(\d{2,5})/', $video["meta"], $matches);

		# If none
		if(count($matches) < 3)
			continue;

		# Update
		sky::$db->make("files")
			->set("width", $matches[1])
			->set("height", $matches[2])
			->where("id", $video["id"])
			->update();

	}

} catch(Exception $e) {

	/* Log if not logged */
	if(!($e instanceof systemException))
		baseException::log($e->getMessage(), "cron exception");

}