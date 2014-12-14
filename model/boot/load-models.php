<?php

require(PATH_MODEL . 'model-includes/class.annotation.php');

require(PATH_MODEL . 'model-includes/class.create-tables.php');

require(PATH_MODEL . 'model-includes/class.intersect-table.php');

$models_dirname = PATH_MODEL . 'tables/';

$models_dir = dir($models_dirname);

$models_array = '';

$model_table = '';

$models = array();

do
{
	$entry = $models_dir->read();

	if ($entry !== '.' && $entry !== '..')
	{
		if (is_file($models_dirname . $entry))
		{
			$annotation = new Annotation($models_dirname . $entry);

			$model = $annotation->getModel();

			$models[] = $model;

			$model_table[$model->class_name] = (!is_null($model->name))? $model->name : strtolower($model->class_name);
			
			require($models_dirname . $entry);
		}
	}
} while($entry !== false);

$create_table = new CreateTables($models);

$models_array = $create_table->getQuery();


require(PATH_MODEL . 'boot/create-tables.php');