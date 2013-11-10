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
	public $title 	 = "Вход";

	public $parentTemplate = "simple";

	/**
	 * Page creation
	 */
	public function main() {

		# render page
		$this->renderContent();

	}

}