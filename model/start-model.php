<?php

if (!defined('PATH_MODEL'))
	define('PATH_MODEL', dirname(__FILE__)) . '/';


require(PATH_MODEL . 'boot/schema.php');

require(PATH_MODEL . 'boot/start-database.php');

require(PATH_MODEL . 'boot/load-models.php');

require(PATH_MODEL . 'model-includes/class.generic-model.php');