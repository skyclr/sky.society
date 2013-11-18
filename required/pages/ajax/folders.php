<?php


# Add folder
if($type == "add")
	jSend(userFolders::add($_POST), "folder");


# Add folder
if($type == "change")
	jSend(userFolders::change($_POST), "folder");


# Get id
$id = vars::post("id", "numeric", "always");


# Delete folder
if($type == "delete")
	jSend(userFolders::delete($id));


# Get folders list
jSend(array(
	"folders" => userFolders::getByParent($id),
	"current" => userFolders::getById($id),
	"files"   => userFiles::getByFolder($id)
));