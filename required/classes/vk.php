<?php

class vk {

	/**
	 * Access params
	 * @var string
	 */
	private static
		$token = "e252e5044585eb1ef530c95a1b4dc23be891686c8eac778dd535a6c1f0ce366250c583a1c44f174044195",
		$methodUrl = "https://api.vk.com/method/";

	/**
	 * Returns upload url
	 * @return mixed
	 */
	public static function photosGetMessagesUploadServer() {
		$data = self::request("photos.getMessagesUploadServer");
		return $data["response"]["upload_url"];
	}



	public static function wallPost($message, $options = array()) {
		$data = self::request("wall.post", array("message" => $message) + $options);
		return $data["response"];
	}

	/**
	 * Uploads image for wall use
	 * @param string $path Image path
	 * @return mixed
	 */
	public static function uploadWallPhotos($path) {

		# Get url
		$data = self::request("photos.getWallUploadServer");

		# Perform file send request
		$result = self::request($data["response"]["upload_url"], array("photo" => "@$path"));

		# Perform vk inner upload
		$data = self::request("photos.saveWallPhoto", $result);

		# Return
		return $data["response"];

	}

	/**
	 * Uploads image for messages use
	 * @param string $path Image path
	 * @return mixed
	 */
	public static function uploadMessagesPhotos($path) {

		# Get url
		$url = self::photosGetMessagesUploadServer();

		# Perform file send request
		$result = self::request($url, array("photo" => "@".$path));

		# Perform vk inner upload
		$data = self::request("photos.saveMessagesPhoto", $result);

		# Return
		return $data["response"];

	}

	public static function photoToAttachment($photo) {
		return "photo" . $photo["owner_id"] . "_" . $photo["id"];
	}

	/**
	 * Performs request
	 * @param string     $method method name
	 * @param array $params Params list
	 * @return mixed
	 * @throws systemErrorException
	 */
	private static function request($method, $params = array()) {

		# Token check
		if(!self::$token)
			throw new systemErrorException("VK no token");

		# Set url
		if(stripos($method, "http://") === false)
			$method = self::$methodUrl . $method;

		# Set token
		$params["access_token"] = self::$token;

		# Perform request
		if(!($result = network::curlRequest($method, array("request" => "POST", "ssl" => 1, "post" => $params, "timeout" => 20))) || empty($result["response"]))
			throw new systemErrorException("No result in response");

		# Parse
		if(is_null($data = json_decode($result["response"], true)))
			throw new systemErrorException("Can't parse result: " . $result["response"]);

		# If error occupied
		if(!empty($data["error"]))
			throw new systemErrorException("Error during '$method' operation({$data["error"]["error_code"]}): " . $data["error"]["error_msg"]);

		# Return parsed
		return $data;

	}

}