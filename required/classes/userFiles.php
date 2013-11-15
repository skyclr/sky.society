<?php

/**
 * Class to with files
 */
class userFiles {


	private static
		/**
		 * Max revision query
		 * @var string
		 */
		$maxRevisionJoin = "SELECT MAX(`id`) as `id`, `fileId` FROM `filesRevisions` GROUP BY `fileId` DESC";

	/**
	 * Adds file
	 * @param $data
	 */
	public static function add($data) {

		$record = $data;


		# Add file record
		$id = sky::$db->make("files")
			->set("owner", auth::$me["id"])
			->set("created", "", "now")
			->insert();


		# Add revision record
		sky::$db->make("filesRevisions")
			->set("fileId", $id)
			->set("name", $record["name"])
			->insert();


		# Return created file
		return self::getById($id);

	}


	public static function getById($id) {



	}

}
