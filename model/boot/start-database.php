<?php

require(PATH_MODEL . 'model-includes/class.model-schema.php');
require(PATH_MODEL . 'model-includes/class.mysql-crud.php');

$model_schema = new ModelSchema(SCHEMA);
$schema = $model_schema->getModelUnitProperties();

$database = new MySQLCrud(
	$schema['database.host'],
	$schema['database.name'],
	$schema['database.user'],
	$schema['database.password']
);