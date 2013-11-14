<?php


if($type == "add")



# Get id
$id = vars::post("id", "numeric", "always");

# Get folders list
jSend(array(
	"folders" => folders::getByParent($id),
	"current" => folders::getById($id)
));