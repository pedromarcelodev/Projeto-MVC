<?php

class Annotation
{

	private $str_model;

	private $class_name;

	private $primary_key;

	private $model;

	public function __construct($dir_model)
	{
		$this->str_model = file_get_contents($dir_model);

		$this->buildModel();
	}

	public function getModel()
	{
		return $this->model;
	}

	private function buildModel()
	{
		$table = $this->table();
		$id = $this->id();
		$collumns = $this->collumn();

		$table->collumns = array();
		$table->collumns[] = $id;

		$table->references = new stdClass();

		$table->references->one_to_one = $this->oneToOne();
		$table->references->one_to_many = $this->oneToMany();
		$table->references->many_to_one = $this->manyToOne();
		$table->references->many_to_many = $this->manyToMany();

		$table->collumns = array_merge($table->collumns, $collumns);

		$this->model = $table;
	}

	private function table()
	{
		preg_match("/\/\/@table[?:\(]?(.*|[?:^,]?|[?:^ ]?)[?:\)]?+[\s]+[class ]+(\w+)/", $this->str_model, $matches);

		$obj = new stdClass();

		$parameters = $this->parameters($matches[1], false);

		$obj = $this->arrayToObjectTable($parameters);

		$obj->class_name = $matches[2];
		$this->class_name = $matches[2];

		return $obj;
	}

	private function id()
	{
		preg_match(
			"/\/\/@id[?:\(]?(.*|[?:^,]?|[?:^ ]?)[?:\)]?+[\s]+(?:[private ]?)+[\$]+(.*);/",
			$this->str_model,
			$matches
		);

		$parameters = $this->parameters($matches[1], true, true);

		$obj = $this->arrayToObjectCollumn($parameters);

		$obj->var = $matches[2];
		$obj->name = (!is_null($obj->name))? $obj->name : strtolower($obj->var);

		$gs = $this->buildGettersAndSetter($matches[2]);

		$obj->get = $gs["get"];
		$obj->set = $gs["set"];

		return $obj;
	}

	private function collumn()
	{
		preg_match_all(
			"/\/\/@collumn[?:\(]?(.*|[?:^,]?|[?:^ ]?)[?:\)]?+[\s]+(?:[private ]?)+[\$]+(.*);/",
			$this->str_model,
			$matches
		);

		return $this->_match_all($matches);
	}

	private function oneToOne()
	{
		preg_match_all(
			"/\/\/@oneToOne[?:\(]?(.*|[?:^,]?|[?:^ ]?)[?:\)]?+[\s]+(?:[private ]?)+[\$]+(.*);/",
			$this->str_model,
			$matches
		);

		return $this->_match_all_refs($matches, true);
	}

	private function oneToMany()
	{
		preg_match_all(
			"/\/\/@oneToMany[?:\(]?(.*|[?:^,]?|[?:^ ]?)[?:\)]?+[\s]+(?:[private ]?)+[\$]+(.*);/",
			$this->str_model,
			$matches
		);

		return $this->_match_all_refs($matches, false);
	}

	private function manyToOne()
	{
		preg_match_all(
			"/\/\/@manyToOne[?:\(]?(.*|[?:^,]?|[?:^ ]?)[?:\)]?+[\s]+(?:[private ]?)+[\$]+(.*);/",
			$this->str_model,
			$matches
		);

		return $this->_match_all_refs($matches, false);
	}

	private function manyToMany()
	{
		preg_match_all(
			"/\/\/@manyToMany[?:\(]?(.*|[?:^,]?|[?:^ ]?)[?:\)]?+[\s]+(?:[private ]?)+[\$]+(.*);/",
			$this->str_model,
			$matches
		);

		return $this->_match_all_refs($matches, true);
	}

	private function parameters($param, $colummn = true, $primary_key = false)
	{
		$parameters = "";

		if(is_string($param) &&  trim($param) != '')
		{
			$r = array(
				"\"" => "",
				"'" => "",
				"(" => "",
				")" => "",
				" " => ""
			);

			$param = strtr($param, $r);
			$param = spliti(",", $param);

			foreach ($param as $m) {
				list($k, $v) = split("=", $m);
				$parameters[$k] = $v;
			}

			if ($colummn == true)
			{
				if ($primary_key == true)
				{
					if (!isset($parameters["key"]) || $parameters["key"] !== "primary_key")
					{
						$parameters["key"] = "primary_key";
					}

					$parameters["nullable"] = "false";
				}
				else
				{
					if (!isset($parameters["nullable"]))
					{
						$parameters["nullable"] = "true";
					}
					else if (is_string($parameters["nullable"]))
					{
						if ($parameters["nullable"] != "false" && $parameters["nullable"] != "true")
						{
							$parameters["nullable"] = "true";
						}
					}
					else
					{
						$parameters["nullable"] = "true";
					}
				}
			}
		}

		return $parameters;
	}

	private function buildGettersAndSetter($var)
	{
		$len = strlen($var);

		for ($i = 0; $i < $len; $i++) {
			if ($i == 0)
			{
				$var[$i] = strtoupper($var[$i]);
			}
			else
			{
				if ($var[$i] == '_')
				{
					$i++;
					$var[$i] = strtoupper($var[$i]);
				}
			}
		}

		$var = str_replace("_", "", $var);

		$getAndSet = array(
			"get" => "get" . $var,
			"set" => "set" . $var
		);

		return $getAndSet;
	}

	private function _match_all($matches)
	{
		$count = count($matches[1]);
		$array = array();

		for ($i = 0; $i < $count; $i++) {
			$obj = new stdClass();

			$parameters = $this->parameters($matches[1][$i]);

			$obj = $this->arrayToObjectCollumn($parameters);

			$obj->var = $matches[2][$i];
			$obj->name = (!is_null($obj->name))? $obj->name : strtolower($obj->var);

			$gs = $this->buildGettersAndSetter($matches[2][$i]);

			$obj->get = $gs["get"];
			$obj->set = $gs["set"];

			$array[] = $obj;
		}

		return $array;
	}

	private function _match_all_refs($matches, $self_relationship)
	{
		$count = count($matches[1]);
		$array = array();

		for ($i = 0; $i < $count; $i++) {
			$obj = new stdClass();

			$parameters = $this->parameters($matches[1][$i]);

			$obj = $this->arrayToObjectReference($parameters, $self_relationship);

			$obj->var = $matches[2][$i];
			$obj->name = (!is_null($obj->name))? $obj->name : strtolower($obj->var);


			$gs = $this->buildGettersAndSetter($matches[2][$i]);

			$obj->get = $gs["get"];
			$obj->set = $gs["set"];

			$array[] = $obj;
		}

		return $array;
	}

	private function arrayToObjectCollumn($arr)
	{
		$m = new stdClass();

		$m->name = (isset($arr["name"]))? trim($arr["name"]) : null;
		$m->type = (isset($arr["type"]))? trim($arr["type"]) : null;
		$m->length = (isset($arr["length"]))? trim($arr["length"]) : null;
		$m->nullable = (isset($arr["nullable"]))? trim($arr["nullable"]) : null;
		$m->key = (isset($arr["key"]))? trim($arr["key"]) : null;

		return $m;
	}

	private function arrayToObjectTable($arr)
	{
		$obj = new stdClass();

		$obj->name = (isset($arr["name"]))? trim($arr["name"]) : strtolower(trim($arr["class_name"]));
		$obj->class_name = (isset($arr["class_name"]))? trim($arr["class_name"]) : null;

		return $obj;
	}

	private function arrayToObjectReference($arr, $self_relationship)
	{
		$obj = new stdClass();

		$obj->name = (isset($arr["name"]))? trim($arr["name"]) : null;
		$obj->referenced_model = (isset($arr["referencedModel"]))? trim($arr["referencedModel"]) : null;
		$obj->owner = (isset($arr["owner"]) && ($arr["owner"] == 'true' || $arr["owner"] == 'false'))? 
			trim($arr["owner"]) : 'false';
		$obj->nullable = (isset($arr["nullable"]))? trim($arr["nullable"]) : null;

		if ($self_relationship == true)
		{
			$obj->self_relationship = (isset($arr["self_relationship"]))? trim($arr["self_relationship"]) : 'false';
		}

		return $obj;
	}

	public function getClassName()
	{
		return $this->class_name;
	}
}