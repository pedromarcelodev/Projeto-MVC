<?php

$alter = array();

foreach ($models_array as $model_arr) {
	$result = $database->getRow('SHOW TABLES LIKE \'' . $model_arr['TABLE'] . '\'');

	if(!$result)
	{
		$database->query($model_arr['CREATE']);
		
		if ($model_arr['ALTER'] != '')
		{
			$alter[] = $model_arr['ALTER'];
		}

		if (!empty($model_arr['MANY_TO_MANY']))
		{
			foreach ($model_arr['MANY_TO_MANY'] as $mtm) {
				$result = $database->getRow('SHOW TABLES LIKE \'' . $mtm['table_name'] . '\'');

				if (!$result)
				{
					$database->query($mtm['query']);
					$alter = array_merge($alter, $mtm['alter']);
				}
			}
		}
	}
}

if (!empty($alter))
{
	foreach ($alter as $al) {
		$database->query($al);
	}
}
