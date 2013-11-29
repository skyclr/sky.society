<?php

class versionControl {
	public static function getRevision() {


		$link = sky::$db->connect();

		try {

			# Prepare transaction
			if($link->beginTransaction() === false)
				throw new databaseException("Can't start transaction");

			# Make update with select
			$link->exec("UPDATE `preferences` SET `data` = `data` + 1 WHERE `section` = 'revision' AND `name` = 'id'");
			$counterData = $link->query("SELECT `data` FROM `preferences` WHERE `section` = 'revision' AND `name` = 'id'");

			# Commit
			$link->commit();

			# Fetch data
			$data = $counterData->fetch(PDO::FETCH_ASSOC);
			$counterData->closeCursor();

			# Return
			return $data["data"];

		} catch(PDOException $e) {
			$link->rollBack();
			throw new databaseException("Database connection error: " . $e->getMessage());
		}

	}
}