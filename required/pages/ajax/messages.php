<?php


# Add new message
if($type == "add")
	jSend(messages::add($_POST), "message");

# Get dialogs list
if($type == "list")
	jSend(messages::getList());

jError("Не указана операция для выполнения");