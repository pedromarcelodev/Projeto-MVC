<?php

define('PATH_CONTROLLER', dirname(__FILE__) . '/');

define('PATH_MODEL', PATH_CONTROLLER . '../model/');

define('PATH_VIEW', PATH_CONTROLLER . '../view/');


/*
|--------------------------------------
| Boot da aplicação
|--------------------------------------
*/

require(PATH_CONTROLLER . '/load.php');