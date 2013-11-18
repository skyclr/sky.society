<?php


# Add folder
if($type == "add")
	jSend(userFiles::add($_GET), "file");


# Get id
$id = vars::post("id", "numeric", "always");


# Delete folder
if($type == "delete")
	jSend(userFolders::delete($id));


# Get folders list
jSend(array(
	"folders" => userFolders::getByParent($id),
	"current" => userFolders::getById($id)
));