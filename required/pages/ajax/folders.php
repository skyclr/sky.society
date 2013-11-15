<?php


# Add folder
if($type == "add")
	jSend(folders::add($_POST), "folder");


# Get id
$id = vars::post("id", "numeric", "always");


# Delete folder
if($type == "delete")
	jSend(folders::delete($id));


# Get folders list
jSend(array(
	"folders" => folders::getByParent($id),
	"current" => folders::getById($id)
));