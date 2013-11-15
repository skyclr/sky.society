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
	 * @return array|Mixed
	 */
	public static function add($data) {

		$record = $data;

		$file = files::inputToFile(sky::location("files") . "files", "random", 0, "ajaxFile");

		var_dump($file);

		# Add file record
		$id = sky::$db->make("files")
			->set("owner", auth::$me["id"])
			->set("created", "", "now")
			->insert();


		# Add revision record
		sky::$db->make("filesRevisions")
			->set("fileId", $id)
			->set("modified", "", "now")
			->set("name", $file["name"])
			->insert();


		# Return created file
		return self::getById($id);

	}

	/**
	 * Gets file by id
	 * @param int $id File id
	 * @return array|Mixed
	 * @throws userErrorException
	 */
	public static function getById($id) {


		# Get folder
		if(!$file = sky::$db->make("files")
			->join("(" . self::$maxRevisionJoin .") as temp", "temp.fileId = files.id")
			->join("filesRevisions", "filesRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->where($id)
			->where("deleted", 0)
			->records(array("filesRevisions.*", "files.owner", "files.created"))
			->get("single"))
			throw new userErrorException("Указанного файла не существует");


		# Return
		return $file;

	}

}
