<?php

/**
 * Class page
 * Used for creation main page
 */
class page extends basePage {


	public

		/**
		 * Page title
		 * @var string|boolean
		 */
		$title 	 = "файлы",


		/**
		 * List of templates used in page
		 * @var array
		 */
		$jsTemplates = array("folders", "files", "users");

	/**
	 * Page creation
	 */
	public function main() {

		# render page
		$this->content = $this->render();

	}

}