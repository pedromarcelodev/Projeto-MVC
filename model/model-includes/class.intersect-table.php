<?php

class IntersectTable
{
	public static function getIntersectTableName($table1, $table2)
	{
		$arr = array($table1, $table2);
		sort($arr);

		$rep = array(
			'tb' => '',
			'_' => ''
		);

		$intersect_table = 'tb_' . strtr($arr[0], $rep) . '_' . strtr($arr[1], $rep);

		return $intersect_table;
	}

	public static function getColumnsSelfRelationship($collumn)
	{
		return array($collumn . 1, $collumn . 2);
	}

	public static function getCollumns($collumn1, $collumn2)
	{
		return "($collumn1, $collumn2)";
	}
}