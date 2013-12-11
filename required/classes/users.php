<?php

/**
 * Class users
 */
class users {

	public static function saveSettings($data) {


	}


	public static function uploadAvatar() {


		# If no uploaded
		if(!files::filesUploaded("avatar"))
			return;


		# If no
		if(!$file = files::uploadFiles(sky::location("temp"), "random", 0, 1, "avatar", "image"))
			throw new userErrorException("Невозможно загрузить изображение");


		# Make big
		images::makeSmallFromFiles($file, self::getUserPath() . "avatars/", 200, 200, "big", 	0, true);
		images::makeSmallFromFiles($file, self::getUserPath() . "avatars/", 100, 100, "medium", 0, true);
		images::makeSmallFromFiles($file, self::getUserPath() . "avatars/",  50,  50, "small", 	0, true);


		# Array to one
		$file = $file[0];


		# Update
		auth::$me["hasAvatar"] = 1;
		auth::$me["avatarExtension"] = $file["extension"];
		auth::$me->save();

	}

	public static function getUserPath($user = false) {


		# Get self if none
		if(!$user) {

			# If not logged
			if(!auth::isLoggedIn())
				throw new systemErrorException("Try to get info of non logged in user");

			# Get current
			$user = auth::$me->get();
		}

		# Return path
		return sky::location("users") . $user["username"] . "/";

	}

	public static function register($id, $data) {


		# Validation
		$data = validator::init($data)
			->rule("username", "trim", "Вы не указали логин")
			->rule("password", "trim", "Вы не указали пароль")
			->get();


		# Check if already
		if($same = sky::$db->make("users")->where("username", $data['username'])->get())
			throw new userErrorException("Пользователь с таким именем уже зарегестрирован");


		# Update
		sky::$db->make("users")->where($id)
			->set("username", $data["username"])
			->set("password", $data["password"], "password")
			->set("activated", 1)
			->update();

		# Return user
		return sky::$db->make("users")->where($id)->get("single");

	}

}