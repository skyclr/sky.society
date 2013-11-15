<?php



/**
 * Class folders
 */
class folders {

	private static
		/**
		 * Max revision query
		 * @var string
		 */
		$maxRevisionJoin = "SELECT MAX(`id`) as `id`, `folderId` FROM `foldersRevisions` GROUP BY folderId DESC";

	/**
	 * Root folder data
	 * @var array
	 */
	public static
		$root = array(
			"name"     => "Корень",
			"id"       => 0,
			"parentId" => false
		);

	public static function add($data) {

		$record = $data;

		$id = sky::$db->make("folders")
			->set("owner", auth::$me["id"])
			->set("created", "", "now")
			->insert();

		sky::$db->make("foldersRevisions")
			->set("folderId", $id)
			->set("name", $record["name"])
			->set("parentId", $record["folderId"])
			->set("modified", "", "now")
			->set("ownerId", auth::$me["id"])
			->insert();

		return self::getById($id);

	}

	public static function getAll() {

		# Get list
		return $folders = sky::$db->make("folders")
			->join("(" . self::$maxRevisionJoin .") as temp", "temp.folderId = folders.id")
			->join("foldersRevisions", "foldersRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->records(array("foldersRevisions.*", "folders.owner", "folders.created"))
			->get();

	}


	public static function getById($id) {

		# Get root
		if($id == 0)
			return self::$root;


		# Get list
		$folder = sky::$db->make("folders")
			->join("(" . self::$maxRevisionJoin .") as temp", "temp.folderId = folders.id")
			->join("foldersRevisions", "foldersRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->where($id)
			->where("deleted", 0)
			->records(array("foldersRevisions.*", "folders.owner", "folders.created"))
			->get("single");


		# No such folder
		if(!$folder)
			throw new userErrorException("Указанной папки не существует");

		return $folder;

	}

	public static function getByParent($id) {

		# Get list
		$folders = sky::$db->make("folders")
			->join("(" . self::$maxRevisionJoin .") as temp", "temp.folderId = folders.id")
			->join("foldersRevisions", "foldersRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->where("foldersRevisions.parentId", $id)
			->records(array("foldersRevisions.*", "folders.owner", "folders.created"))
			->get();

		return $folders;

	}

	public static function delete($id) {


		# Get folder
		$folder = self::getById($id);


		# Add revision
		sky::$db->make("foldersRevisions")
			->set("folderId", $id)
			->set("name", $folder["name"])
			->set("parentId", $folder["parentId"])
			->set("modified", "", "now")
			->set("ownerId", auth::$me["id"])
			->set("deleted", 1)
			->insert();


		# Return
		return "Deleted";

	}


}