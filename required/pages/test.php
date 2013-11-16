<?php

/**
 * Class page
 * Used for creation main page
 */
class page extends basePage {

	/**
	 * Page title
	 * @var string html
	 */
	public $title 	 = "test";

	/**
	 * Page creation
	 */
	public function main() {

		# If file
		if(files::filesUploaded()) {

			# Upload file
			//$file = files::uploadFiles(sky::location("files"));

		}

		phpinfo();


		//$image = vk::uploadWallPhotos(sky::location("files") . "1.jpg");
		//$post = vk::wallPost("Тест фоток", array("from_group" => 1, "owner_id" => -60733873, "attachments" => $image[0]["id"]));


		# render page
		$this->renderContent();

	}

}