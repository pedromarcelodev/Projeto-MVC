<?php

class ModelFactory
{

	private $model;

	private $collumns;

	private $one_to_one;

	private $one_to_many;

	private $many_to_one;

	private $many_to_many;

	private $obj_model_class;

	public function __construct($model)
	{
		$this->model = $model;
		$this->collumns = $model->collumns;
		$this->one_to_one = $model->references->one_to_one;
		$this->one_to_many = $model->references->one_to_many;
		$this->many_to_one = $model->references->many_to_one;
		$this->many_to_many = $model->references->many_to_many;

		$class = $model->class_name;
		$this->obj_model_class = new $class();
	}

	public function setCollumnsValues($values)
	{
		foreach ($this->collumns as $collumn) {
			$this->obj_model_class->{$collumn->set}($values[$collumn->name]);
		}
	}


}