<?php

if($type == "add")
	jSend(comments::add($_POST), "comment");

jError("Не указана операция для выполнения");