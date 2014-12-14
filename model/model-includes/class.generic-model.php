<?php

final class GenericModel
{
	private $models;

	private $database;

	public function __construct($schema)
	{
		global $models;

		$this->models = $models;

		$model_schema = new ModelSchema($schema);
		$schema = $model_schema->getModelUnitProperties();

		$this->database = new MySQLCrud(
			$schema['database.host'],
			$schema['database.name'],
			$schema['database.user'],
			$schema['database.password']
		);
	}

	public function find($id, $m)
	{
		if (is_string($m))
		{
			$model = $this->getModelByName($m);
			$m = new $m();

			$m = $this->setPropertiesObject($m, $model, $id);

			return $m;
		}
		else if (is_object($m))
		{
			$model = $this->getModel($m);
			$m = $this->setPropertiesObject($m, $model, $id);

			return $m;
		}
		else
		{
			return null;
		}
	}

	public function save($obj)
	{
		$model = $this->getModel($obj);

		if (is_null($model))
		{
			return null;
		}
		else
		{
			$values = $this->initValues($obj, $model);
			$set_id = $model->collumns[0]->set;

			$insert_id = $this->database->insert($model->name, $values);

			$obj->{$set_id}($insert_id);

			if (!empty($model->references->many_to_many))
			{
				$this->initValuesManyToMany($obj, $model);
			}

			return $obj;
		}
	}

	private function initValues($obj, $model)
	{
		$count = count($model->collumns);

		for ($i = 1; $i < $count; $i++) {
			$collumn = $model->collumns[$i];
			$values[$collumn->name] = $obj->{$collumn->get}();
		}

		if (!empty($model->references->one_to_one))
		{
			foreach ($model->references->one_to_one as $oto) {
				if ($oto->owner == 'false')
				{
					$ref = $obj->{$oto->get}();
					if ($ref)
					{
						$ref_model = $this->getModel($ref);
						$values[$oto->name] = $ref->{$ref_model->collumns[0]->get}();
					}
				}

			}
		}

		if (!empty($model->references->many_to_one))
		{
			foreach ($model->references->many_to_one as $mto) {
				$ref = $obj->{$mto->get}();
				if ($ref)
				{
					$ref_model = $this->getModel($ref);
					$values[$mto->name] = $ref->{$ref_model->collumns[0]->get}();
				}
			}
		}

		return isset($values)? $values : null;
	}

	private function initValuesManyToMany($obj, $model)
	{
		foreach ($model->references->many_to_many as $mtm) {
			if ($mtm->self_relationship == 'true')
			{
				$refs = $obj->{$mtm->get}();
				if ($refs)
				{
					if (!empty($refs))
					{
						$table_name = IntersectTable::getIntersectTableName($model->name, $model->name);
						$id = $model->collumns[0]->name;
						$get_id = $model->collumns[0]->get;
						$id1 = $id . '1';
						$id2 = $id . '2';

						foreach ($refs as $ref) {
							$id_ref = $ref->{$get_id}();
							$id_this = $obj->{$get_id}();
							
							$this->database->query("INSERT INTO $table_name($id1, $id2) 
								VALUES($id_this, $id_ref)");
						}
					}
				}
			}
			else
			{
				$refs = $obj->{$mtm->get}();
				if ($refs)
				{
					if (!empty($refs))
					{
						$ref_model = $mtm->referenced_model;
						$ref_model = $this->getModel(new $ref_model());
						$table_name = IntersectTable::getIntersectTableName($model->name, $ref_model->name);
						$id = $model->collumns[0]->name;
						$get_id = $model->collumns[0]->get;
						$id1 = $id;
						$id2 = $ref_model->collumns[0]->name;

						foreach ($refs as $ref) {
							$id_this = $obj->{$get_id}();
							$id_ref = $ref->{$ref_model->collumns[0]->get}();

							$this->database->query("INSERT INTO $table_name($id1, $id2) 
								VALUES($id_this, $id_ref)");
						}
					}
				}
			}
		}
	}

	private function getModel($obj)
	{
		foreach ($this->models as $model) {
			if ($obj instanceof $model->class_name) return $model;
		}

		return null;
	}

	private function getModelByName($model_name)
	{
		foreach ($this->models as $model) {
			if ($model_name == $model->class_name) return $model;
		}

		return null;
	}

	private function setValues($values, $class_name)
	{
		$obj = new $class_name();
		$methods = get_class_methods($obj);

		foreach ($methods as $method) {
			if (strpos($method, 'set'))
			{
				
			}
		}
	}

	private function setPropertiesObject($m, $model, $id)
	{
		$values = $this->database->searchById($model->name, $model->collumns[0]->name, $id);
		$m = $this->setCollumnsInObject($m, $model, $values);

		if (!empty($model->references->one_to_one))
		{
			$m = $this->setOneToOneInObject($m, $model, $values);
		}

		if (!empty($model->references->one_to_many))
		{
			$m = $this->setOneToManyInObject($m, $model, $values);
		}

		if (!empty($model->references->many_to_one))
		{
			$m = $this->setManyToOneInObject($m, $model, $values);
		}

		if (!empty($model->references->many_to_many))
		{
			$m = $this->setManyToManyInObject($m, $model, $values);
		}

		return $m;
	}

	private function setCollumnsInObject($obj, $model, $values)
	{
		foreach ($model->collumns as $collumn) {
			$obj->{$collumn->set}($values[$collumn->name]);
		}

		return $obj;
	}

	private function setOneToOneInObject($obj, $model, $values)
	{
		foreach ($model->references->one_to_one as $oto) {
			if (!is_null($values[$oto->name]))
			{
				$class_name = $oto->referenced_model;
				$ref = new $class_name();
				$ref_model = $this->getModel($ref);
				$ref_pk = $ref_model->collumns[0]->name;
				$ref_values = $this->database->searchById($ref_model->name, $ref_pk, $values[$oto->name]);

				foreach ($ref_model->collumns as $collumn) {
					$ref->{$collumn->set}($ref_values[$collumn->name]);
				}

				$obj->{$oto->set}($ref);
			}
		}

		return $obj;
	}

	private function setOneToManyInObject($obj, $model, $values)
	{
		foreach ($model->references->one_to_many as $otm) {
			if (!is_null($values[$otm->name]))
			{
				$class_name = $otm->referenced_model;
				$ref = new $class_name();
				$ref_model = $this->getModel($ref);
				$ref_pk = $ref_model->collumns[0]->name;
				$ref_values = $this->database->searchById($ref_model->name, $ref_pk, $values[$otm->name]);

				foreach ($ref_model->collumns as $collumn) {
					$ref->{$collumn->set}($ref_values[$collumn->name]);
				}

				$obj->{$otm->set}($ref);
			}
		}

		return $obj;
	}

	private function setManyToOneInObject($obj, $model, $values)
	{
		foreach ($model->references->many_to_one as $mto) {
			if (!is_null($values[$mto->name]))
			{
				$class_name = $mto->referenced_model;
				$ref = new $class_name();
				$ref_model = $this->getModel($ref);
				$ref_pk = $ref_model->collumns[0]->name;
				$ref_values = $this->database->searchById($ref_model->name, $ref_pk, $values[$mto->name]);

				foreach ($ref_model->collumns as $collumn) {
					$ref->{$collumn->set}($ref_values[$collumn->name]);
				}

				$obj->{$mto->set}($ref);
			}
		}

		return $obj;
	}

	private function setManyToManyInObject($obj, $model, $values)
	{
		foreach ($model->references->many_to_many as $mtm) {
			if ($mtm->self_relationship == 'true')
			{
				$table_name = IntersectTable::getIntersectTableName($model->name, $model->name);
			}
			else
			{
				$ref_model = $mtm->referenced_model;
				$ref_model = $this->getModel(new $ref_model());
				$table_name = IntersectTable::getIntersectTableName($model->name, $ref_model->name);
			}

			$id = $obj->{$model->collumns[0]->get}();
			$class_name = $mtm->referenced_model;
			$ref = new $class_name();
			$ref_model = $this->getModel($ref);
			$ref_pk = $ref_model->collumns[0]->name;
			$ref_values = $this->getManyToManyValues(
				$id,
				$table_name,
				$ref_model->name,
				$model->collumns[0]->name,
				$ref_pk,
				$mtm->self_relationship
			);

			$refs_arr = array();

			foreach ($ref_values as $ref_val) {
				$ref = new $class_name();

				foreach ($ref_model->collumns as $collumn) {
					$ref->{$collumn->set}($ref_val[$collumn->name]);
				}

				$refs_arr[] = $ref;
			}

			$obj->{$mtm->set}($refs_arr);
		}

		return $obj;
	}

	private function getManyToManyValues($id, $table_intersect, $table_ref, $collumn_name, $collumn_ref, $self_relationship = 'false')
	{
		if ($self_relationship == 'true')
		{
			$where = "{$collumn_name}1 = '$id' OR {$collumn_name}2 = '$id'";

			$vals = $this->database->getList("SELECT * FROM $table_intersect WHERE $where");
			$val_refs = array();

			if (empty($vals))
			{
				return array();
			}

			foreach ($vals as $val) {
				if ($val[$collumn_name . 1] == $id)
				{
					$val_refs[] = $this->database->searchById($table_ref, $collumn_name, $val[$collumn_name . 2]);
				}
				else
				{
					$val_refs[] = $this->database->searchById($table_ref, $collumn_name, $val[$collumn_name . 1]);
				}
			}

			return $val_refs;
		}
		else
		{

		}
	}

}