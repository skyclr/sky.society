<?php

/**
 * Class to with files
 */
class userFiles {

	public static

		/**
		 * Max revision query
		 * @var string
		 */
		$maxRevisionJoin = "SELECT MAX(`id`) as `id`, `fileId` FROM `filesRevisions` GROUP BY `fileId` DESC";

	/**
	 * Adds file
	 * @param $data
	 * @throws userErrorException
	 * @return array|Mixed
	 */
	public static function add($data) {

		$record = $data;
		$thumb = ""; $out = "";

		# Upload file
		if(!$file = files::uploadFiles(sky::location("files") . "/files/", "random", 0, 1, "files"))
			throw new userErrorException("Файл небыл загружен");


		try {

			# Get thumb
			if($file[0]["type"] == "image") {
				images::makeSmallFromFiles($file, sky::location("files") . "/thumbs/", 300, 200, "random", "s_", true);
				$thumb = $file[0]["smallFileName"];
			}


			# If video we get special thumb
			if($file[0]["type"] == "video") {

				/* Init */
				$out = null;
				$ret = 0;
				$name = utils::getRandomString(10);


				# FFmpeg thumb making string
				$exec = sky::location("external") . "ffmpeg -i {$file[0]["fileLocation"]} -ss 00:00:01.000 -f image2 -vframes 1 /tmp/$name.jpg 2>&1";

				# perform shell command
				exec($exec, $out, $ret);

				# If error
				if($ret)
					systemException::log("Can't create thumb for video {$file[0]["fileName"]}, reason: " . var_export($out, true) . "\n$exec");

				# If ok
				else {
					$thumbName = files::makeName(sky::location("files") . "/thumbs/", "random", "sv_", "", "jpg");
					images::resizeToFile("/tmp/$name.jpg", $thumbName, 300, 200, true);
					$thumb = files::getName($thumbName) . ".jpg";
				}
			}
		} catch(Exception $e) {
			files::deleteFile($file[0]["fileLocation"]);
		}


		# Add file record
		$query = sky::$db->make("files")
			->set("owner", auth::$me["id"])
			->set("created", "", "now")
			->set("location", $file[0]["fileName"])
			->set("thumb", $thumb)
			->set("extension", $file[0]["extension"])
			->set("type", $file[0]["type"])
			->set("size", $file[0]["size"]);


		# If video
		if($file[0]["type"] == "video")
			$query->set("meta", implode("\n", $out));


		# Set image specific
		if($file[0]["type"] == "image") {
			$query
			->set("width", $file[0]["width"])
			->set("height", $file[0]["height"]);
		}


		# Insert
		$id = $query->insert();


		# Add revision record
		sky::$db->make("filesRevisions")
			->set("revision", versionControl::getRevision())
			->set("ownerId", auth::$me["id"])
			->set("folderId", $data["folderId"])
			->set("fileId", $id)
			->set("modified", "", "now")
			->set("name", $file[0]["name"])
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
			->where("filesRevisions.deleted", 0)
			->records(array("filesRevisions.*", "owner", "created", "thumb", "extension", "location", "type"))
			->get("single"))
			throw new userErrorException("Указанного файла не существует");


		# Return
		return $file;

	}

	/**
	 * Gets file by folder id
	 * @param      $id
	 * @param bool $lastThumb
	 * @param int  $offset
	 * @return array|Mixed
	 */
	public static function getByFolder($id, $lastThumb = false, $offset = 0) {


		# Prepare request
		$request = sky::$db->make("files")
			->join("(" . self::$maxRevisionJoin .") as temp", "temp.fileId = files.id")
			->join("filesRevisions", "filesRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->where("filesRevisions.folderId", $id)
			->where("filesRevisions.deleted", 0)
			->order("created", "desc")
			->records(array("filesRevisions.*", "owner", "created", "thumb", "extension", "location", "type"));


		# Set page offset
		$request
			->limit(auth::$me->pref("perPage"))
			->offset($offset);


		# Last with thumb
		if($lastThumb)
			$request->where("thumb", "", "!=")->limit(1);


		# Get folder
		if(!$files = $request->get())
			return array();


		# Shortcut
		foreach($files as $i => $file) {
			if(mb_strlen($file["name"]) > 30)
				$files[$i]["name"] = substr($file["name"], 0 , 30) . "…";
		}

		# Return
		return $files;

	}

	/**
	 * Deletes file by id
	 * @param int $id File id
	 * @return string
	 */
	public static function delete($id) {


		# Get file
		$file = self::getById($id);


		# Add revision
		sky::$db->make("filesRevisions")
			->set("revision", versionControl::getRevision())
			->set("fileId", $id)
			->set("name", $file["name"])
			->set("folderId", $file["folderId"])
			->set("modified", "", "now")
			->set("ownerId", auth::$me["id"])
			->set("deleted", 1)
			->insert();


		# Return
		return "Deleted";

	}

	/**
	 * Returns file full info
	 * @param $id
	 * @throws userErrorException
	 * @return array
	 */
	public static function getFullInfo($id) {

		# Get folder
		if(!$file = sky::$db->make("files")
			->join("(" . self::$maxRevisionJoin .") as temp", "temp.fileId = files.id")
			->join("filesRevisions", "filesRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->where($id)
			->where("filesRevisions.deleted", 0)
			->records(array("filesRevisions.*", "owner", "created", "thumb", "extension", "location", "type", "width", "height"))
			->get("single"))
			throw new userErrorException("Указанного файла не существует");


		# Get next
		$file["next"] = sky::$db->make("files")
			->join("(" . self::$maxRevisionJoin .") as temp", "temp.fileId = files.id")
			->join("filesRevisions", "filesRevisions.id = temp.id")
			->where("filesRevisions.deleted", 0)
			->where("filesRevisions.folderId", $file["folderId"])
			->where("id", $file["fileId"], "<")
			->order("id")
			->limit(1)
			->records("id")
			->get("value");


		# Get parent folder
		$parent = userFolders::getById($file["folderId"]);

		# Path
		$path = userFolders::getPath($parent);
		$path[] = $parent;


		# Comments
		$comments = comments::get($id, "file");


		# Return
		return array(
			"file" 	    => $file,
			"path" 		=> $path,
			"parent" 	=> $parent,
			"comments"  => $comments
 		);

	}

}
