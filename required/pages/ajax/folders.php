<?php


if($type == "add")
	jSend(folders::add($_POST), "id");


# Get id
$id = vars::post("id", "numeric", "always");

# Get folders list
jSend(array(
	"folders" => folders::getByParent($id),
	"current" => folders::getById($id)
));