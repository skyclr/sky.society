<?php

# Get folders list
jSend(folders::getByParent(vars::post("id", "numeric", "always")), "folders");