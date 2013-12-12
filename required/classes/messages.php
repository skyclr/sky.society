<?php

/**
 * Class messages
 */
class messages {

	/**
	 * Returns dialog with specified user
	 * @param int $partner Partner id
	 * @return array
	 */
	public static function getDialog($partner) {


		# Prepare result
		$result = array("messages" => array());


		# Get user
		$result["user"] = users::getById($partner);


		# Join
		$dialog = sky::$db->make("messages")
			->where("to", auth::$me["id"], "sender")
			->where("from", $partner, "sender")
			->where("to", $partner, "OR", "recipient")
			->where("from", auth::$me["id"], "recipient")
			->order("created")
			->get();


		# If no dialog
		if(!$dialog)
			return $result;


		# Go through
		foreach($dialog as $message)
			$result["messages"][] = self::compile($message);


		# Return result
		return $result;

	}

	/**
	 * Adds new message
	 * @param array $data Message data
	 * @return Int
	 */
	public static function add($data) {


		# Validate
		$data = validator::init($data)
			->rule("id", "positive", "Не указан получатель сообщения")
			->rule("message", "trim", "Не указан текст сообщения")
			->get();


		# Add new message
		return sky::$db->make("messages")
			->set("to", $data["id"])
			->set("from", auth::$me["id"])
			->set("created", "", "now")
			->set("message", $data["message"])
			->insert();

	}

	/**
	 * Gets number of unread messages
	 * @return Mixed
	 */
	public static function getUnreadCount() {

		# Get number of messages
		return sky::$db->make("messages")
			->where("to", auth::$me["id"], "private")
			->where("read", 0)
			->records("COUNT(*)")
			->get("value");

	}

	/**
	 * Gets list of dialogs
	 * @return array
	 */
	public static function getList() {


		# Prepare result
		$result = array("pages" => 0, "list" => array());


		# Join
		$join = sky::$db->make("messages")
			->where("to", auth::$me["id"], "private")
			->where("from", auth::$me["id"], "OR", "private")
			->records(array("MAX(id) as max", "IF(`to` = '" . auth::$me["id"] . "', `from`, `to`) as `opponent`"))
			->group("opponent")
			->get("query");


		# Get list
		if(!$list = sky::$db->make("$join as `m`")
			->join("messages", "m.id = messages.id")
			->get())
			return $result;


		# compile
		foreach($list as $message)
			$result["list"][] = self::compile($message);


		# Return
		return $result;

	}

	/**
	 * Compiles single message
	 * @param $message
	 * @return mixed
	 */
	private static function compile($message) {
		return $message;
	}

}