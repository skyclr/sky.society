<?php

/**
 * Class folders
 */
class folders {

	/**
	 * Root folder data
	 * @var array
	 */
	public static $root = array(

	);

	public static function getAll() {

		# Get max revisions
		$join = sky::$db->make("foldersRevisions")
			->group("folderId")
			->records(array("MAX(id) as `id`", "folderId"))
			->get("query");

		# Get list
		return $folders = sky::$db->make("folders")
			->join("($join) as temp", "temp.folderId = folders.id")
			->join("foldersRevisions", "foldersRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->records(array("foldersRevisions.*", "folders.owner", "folders.created"))
			->get();

	}

}