<?php

if (!defined('PATH_CONTROLLER'))
	define('PATH_CONTROLLER', dirname(__FILE__) . '/');

if (!defined('PATH_MODEL'))
	define('PATH_MODEL', PATH_CONTROLLER . '../model/');

if (!defined('PATH_VIEW'))
	define('PATH_VIEW', PATH_CONTROLLER . '../view/');

require(PATH_MODEL . 'start-model.php');