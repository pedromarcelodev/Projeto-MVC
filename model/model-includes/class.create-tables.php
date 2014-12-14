<?php

class CreateTables
{

	private $models;

	private $data_models;

	private $query_arr;

	public function __construct($models)
	{
		$this->models = $models;

		$this->data_models = array();

		$this->query_arr = array(
			'TABLE' => '',
			'CREATE' => '',
			'ALTER' => '',
			'MANY_TO_MANY' => array()
		);

		$this->buildModels();
	}

	public function getQuery()
	{
		return $this->data_models;
	}

	private function buildModels()
	{
		foreach ($this->models as $model) {
			$table_name = $model->name;
			$class_name = $model->class_name;
			$collumns = $model->collumns;
			$references = $model->references;

			$refs = array(
				$references->one_to_one,
				$references->many_to_one
			);

			$table = $table_name;
			$id_table = (!is_null($collumns[0]->name))? $collumns[0]->name : strtolower($collumns[0]->var);
			$id_length = (!is_null($collumns[0]->length))? '(' . $collumns[0]->name . ')' : '';
			$id_type = $collumns[0]->type;

			$t = 'CREATE TABLE ' . $table . '(';

			$t = $this->buildCollumns($collumns, $t);

			$t = $this->buildReferences($refs, $t, $table);

			if (!empty($references->many_to_many))
			{
				$this->buildManyToMany($references->many_to_many, $table, $id_table, $id_type . $id_length);
			}

			$this->query_arr['TABLE'] = $table;
			$this->query_arr['CREATE'] = $t;

			$this->data_models[] = $this->query_arr;

			$this->query_arr = array(
				'TABLE' => '',
				'CREATE' => '',
				'ALTER' => '',
				'MANY_TO_MANY' => array()
			);
		} //end models

		ksort($this->data_models);
	}

	private function buildCollumns($collumns, $str_table)
	{
		$i = 1;
		$count = count($collumns);

		foreach ($collumns as $collumn) {
			$c_name = (!is_null($collumn->name))? $collumn->name : strtolower($collumn->var);
			$type = $collumn->type;
			$length = (!is_null($collumn->length))? '(' . $collumn->length . ')' : '';
			$nullable = ($collumn->nullable == 'true')? 'null' : 'not null';

			$key = '';
			$auto_increment = '';

			if (!is_null($collumn->key))
			{
				if($collumn->key == 'primary_key' && strpos(strtolower($type), 'int') !== false)
				{
					$auto_increment = ' auto_increment';
				}
				$key = ' ' . str_replace('_', ' ', $collumn->key);
			}

			if ($i == $count)
			{
				$str_table .= $c_name . ' ' . $type . $length . ' ' . $nullable . $key . $auto_increment . ');';
			}
			else 
			{
				$str_table .= $c_name . ' ' . $type . $length . ' ' . $nullable . $key . $auto_increment . ', ';
			}

			$i++;
		} //end collumns

		return $str_table;
	}

	private function buildReferences($references, $str_table, $table)
	{
		$cols = '';
		$alters = '';

		foreach ($references as $reference) { //one-to-one, one-to-many, many-to-one, many-to-many
			if (!empty($reference))
			{
				$i = 1;
				$c_rels = count($reference);

				foreach ($reference as $rel) {
					if ($rel->owner == 'false')
					{
						$c_name = (!is_null($rel->name))? $rel->name : strtolower($rel->var);

						$ref_model = self::getReferencedModel($this->models, $rel->referenced_model);

						if (!is_null($ref_model->name))
						{
							$ref_table = $ref_model->name;
						}
						else
						{
							$ref_table = strtolower($ref_model->class_name);
						}

						$ref_pk = $ref_model->collumns[0];

						if (!is_null($ref_pk->name))
						{
							$ref_id = $ref_pk->name;
						}
						else
						{
							$ref_id = strtolower($ref_pk->var);
						}
						
						$type = $ref_pk->type;
						$length = (!is_null($ref_pk->length))? '(' . $ref_pk->length . ')' : '';
						$nullable = ($rel->nullable == 'true')? 'null' : 'not null';

						if ($i == $c_rels)
						{
							$cols .= $c_name . ' ' . $type . $length . ' ' . $nullable;
						}
						else
						{
							$cols .= $c_name . ' ' . $type . $length . ' ' . $nullable . ', ';
						}

						$alters .= 'ALTER TABLE ' . $table . ' ADD CONSTRAINT fk_' . $c_name .
							' FOREIGN KEY(' . $c_name . ') REFERENCES ' . $ref_table . '(' . $ref_id . ');';
					}
					$i++;
				}

				$str_table = str_replace(');', ', ' . $cols . ');', $str_table);
				
				$cols = '';
			}
		} //end references

		$this->query_arr['ALTER'] = $alters;

		return $str_table;
	}

	private function buildManyToMany($many_to_many, $table, $id_table, $type_id)
	{
		$i = 1;
		$count = count($many_to_many);

		foreach ($many_to_many as $rel) {
			$c_name = (!is_null($rel->name))? $rel->name : strtolower($rel->var);
			$ref_t_intersect2 = strtr($c_name, array('id' => '', '_' => ''));

			$ref_model = self::getReferencedModel($this->models, $rel->referenced_model);

			if (!is_null($ref_model->name))
			{
				$ref_table = $ref_model->name;
				$ref_t_intersect1 = strtr($ref_table, array('tb' => '', '_' => ''));
			}
			else
			{
				$ref_table = strtolower($ref_model->class_name);
			}

			$ref_pk = $ref_model->collumns[0];

			if (!is_null($ref_pk->name))
			{
				$ref_id = $ref_pk->name;
			}
			else
			{
				$ref_id = strtolower($ref_pk->var);
			}

			if ($ref_id == $id_table)
			{
				$id_table1 = $id_table . '1';
				$ref_id1 = $ref_id . '2';
			}
			else
			{
				$id_table1 = $id_table;
				$ref_id1 = $ref_id;
			}

			$type = $ref_pk->type;
			$length = (!is_null($ref_pk->length))? '(' . $ref_pk->length . ')' : '';
			$nullable = ($rel->nullable == 'true')? 'null' : 'not null';

			// creating intersect table
			$t_intersect = array($ref_t_intersect1, $ref_t_intersect2);
			sort($t_intersect);

			$t_inters_name = 'tb_' . $t_intersect[0] . '_' . $t_intersect[1];
			$t_inters_id = 'id_' .  $t_intersect[0] . '_' . $t_intersect[1];

			$t_inters = 'CREATE TABLE ' . $t_inters_name . '(';
			$t_inters .= $t_inters_id . ' bigint(20) not null primary key auto_increment,';
			$t_inters .= $id_table1 . ' ' . $type_id . ' not null,';
			$t_inters .= $ref_id1 . ' ' . $type . $length . ' not null);';
			
			$alter1 = 'ALTER TABLE ' . $t_inters_name . ' ADD CONSTRAINT fk_' . $t_intersect[0] . '_' . $t_intersect[1]
				. '1 FOREIGN KEY(' . $id_table1 . ') REFERENCES ' . $table . '(' . $id_table . ');';
			$alter2 = 'ALTER TABLE ' . $t_inters_name . ' ADD CONSTRAINT fk_' . $t_intersect[0] . '_' . $t_intersect[1]
				. '2 FOREIGN KEY(' . $ref_id1 . ') REFERENCES ' . $ref_table . '(' . $ref_id . ');';

			$this->query_arr['MANY_TO_MANY'][] = array(
				'table_name' => $t_inters_name,
				'query' => $t_inters,
				'alter' => array($alter1, $alter2)
			);

			$i++;
		}
	}

	public static function getReferencedModel($models, $model_name)
	{
		foreach ($models as $model) {
			if ($model->class_name == $model_name) return $model;
		}
	}
}