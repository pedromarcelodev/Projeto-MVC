<?php

class ModelSchema
{

	private $model_schema;

	private $arr_model_unit;

	private $dir;

	public function __construct($model_unit)
	{
		if (!is_string($model_unit))
			throw new Exception('É necessário informar o model unit como string no construtor da classe ModelSchema');
		
		$this->dir = dirname(__FILE__);

		$xml = file_get_contents($this->dir . '/../../model/model-schema.xml');

		$this->model_schema = new SimpleXMLElement($xml);

		$this->buildArrayModelUnit($model_unit);
	}

	public function getModelUnitProperties()
	{
		return $this->arr_model_unit;
	}

	private function buildArrayModelUnit($model_unit)
	{
		$mu = $this->searchModelUnit($model_unit);

		$arr_mu = array('sgbd' => (String) $mu['sgbd']);

		foreach ($mu->property as $property) {
			$arr_mu = array_merge($arr_mu, array((String) $property['name'] => (String) $property['value']));
		}

		$this->arr_model_unit = $arr_mu;
	}

	private function searchModelUnit($model_unit)
	{
		foreach ($this->model_schema->{'model-unit'} as $mu) {
			if ($mu['name'] == $model_unit)
			{
				return $mu;
			}
		}

		return null;
	}

}