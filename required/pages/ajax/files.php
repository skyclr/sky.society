<?php


# Add folder
if($type == "add")
	jSend(userFiles::add($_GET), "file");


# Get id
$id = vars::post("id", "numeric", "always");


# Delete folder
if($type == "delete")
	jSend(userFiles::delete($id));


# Delete folder
if($type == "more")
	jSend(userFiles::getByFolder($id, false, vars::post("offset", "numeric", "always")), "files");

jError("Не указана операция для выполнения");