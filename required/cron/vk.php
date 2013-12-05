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

	# Prepare photos request
	$request = sky::$db->make("files")
		->join("(" . userFiles::$maxRevisionJoin .") as temp", "temp.fileId = files.id")
		->join("filesRevisions", "filesRevisions.id = temp.id")
		->where("thumb", "", "!=")
		->where("filesRevisions.fileId", null, "!=")
		->where("created", sky::$config["export"]["vk"], ">");


	# Total files
	if(!$total = $request->same()->records("count(*)")->get("value"))
		die("Nothing to export");


	# get photos
	$files = $request->limit(5)->order("created")->get();


	# If none
	if(!$files)
		die("Nothing to export");


	# Upload photos
	$uploaded = array();
	foreach($files as $photo) {
		echo "Upload {$photo["thumb"]}\n";
		$data = vk::uploadWallPhotos(sky::location("files") . "thumbs/{$photo["thumb"]}");
		$uploaded[] = $data[0]["id"];
	}


	# Make post
	$post = vk::wallPost("Загружены новые фотографии(всего $total)", array("from_group" => 1, "owner_id" => -60733873, "attachments" => implode($uploaded, ",")));


	# Dump
	sky::$db->make(sky::$config["preferences"]["external"])
		->set("data", date(DB2::DATETIME_SQL))
		->where("section", "export")
		->where("name", "vk")
		->update();

} catch(Exception $e) {

	/* Log if not logged */
	if(!($e instanceof systemException))
		baseException::log($e->getMessage(), "cron exception");

}