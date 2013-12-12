<?php

# Add new comment
if($type == "add")
	jSend(comments::add($_POST), "comment");


# Get list of not read comments



# If wrong operation
jError("Не указана операция для выполнения");