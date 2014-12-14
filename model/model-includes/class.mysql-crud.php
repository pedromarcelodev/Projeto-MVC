<?php

class MySQLCrud
{

	private $connection = false;
	private $dbh;
	private $table;
	private $primary_key;
	private $dbname;
	private $dbhost;
	private $dbuser;
	private $dbpassword;


	public function __construct($dbhost, $dbname, $dbuser, $dbpassword)
	{
		$this->dbhost = $dbhost;
		$this->dbname = $dbname;
		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
	}

	private function dbConnect()
	{
		$this->connection = mysql_connect($this->dbhost, $this->dbuser, $this->dbpassword);

		if (!$this->connection)
		{
			die('Não foi possível connectar: ' . mysql_error());
			return false;
		}

		$this->dbh = mysql_select_db($this->dbname, $this->connection);

		if (!$this->dbh)
		{
			mysql_close($this->connection);
			die('Não foi possível conectar ao banco: ' . mysql_error());
			return false;
		}
	}

	private function dbDisconnect()
	{
		if ($this->connection)
		{
			@mysql_close($this->dbh);
			$this->connection = false;
		}
	}

	public function getVar($sql)
	{		
		$this->dbConnect();

		$query = mysql_query($sql);

		if (!$query)
		{
			$this->dbDisconnect();
			return false;
		}

		$result = mysql_fetch_array($query);

		$this->dbDisconnect();

		if (!$result) return false;

		list($select, $key, $str) = split(' ', $sql, 3);

		if ($key == '*')
		{
			return false;
		}
		else
		{
			return $result[$key];
		}
	}

	public function getRow($sql)
	{		
		$this->dbConnect();

		$query = mysql_query($sql);

		if (!$query)
		{
			$this->dbDisconnect();
			return false;
		}

		$result = mysql_fetch_array($query);

		$this->dbDisconnect();

		if (!$result) return false;

		return $result;
	}

	public function getList($sql)
	{		
		$this->dbConnect();

		$query = mysql_query($sql);

		if (!$query)
		{
			$this->dbDisconnect();
			return false;
		}

		$results = array();

		while ($array = mysql_fetch_array($query)) {
			$results[] = $array;
		}

		$this->dbDisconnect();

		if (!$results || empty($results)) return false;

		return $results;
	}

	public function query($sql)
	{
		$this->dbConnect();

		$query = mysql_query($sql);

		$this->dbDisconnect();
	}

	public function insert($table, $values = array())
	{
		$meta = '(';
		$v = '(';
		$i = 1;
		$qtdValues = count($values);

		foreach ($values as $key => $value) {

			if ($i == $qtdValues)
			{
				$meta = $meta . $key . ')';
				
				if (is_string($value))
				{
					$v = $v . '\'' . mysql_real_escape_string($value) . '\')';
				}
				else
				{
					$v = $v . $value . ')';
				}
			}
			else
			{
				$meta = $meta . $key . ',';
				if (is_string($value))
				{
					$v = $v . '\'' . mysql_real_escape_string($value) . '\',';
				}
				else
				{
					$v = $v . $value . ',';
				}
			}

			$i++;
		}

		$this->dbConnect();

		mysql_query("INSERT INTO $table $meta VALUES $v", $this->connection);

		$insert_id = mysql_insert_id();

		$this->dbDisconnect();

		return $insert_id;
	}

	public function update($values = array(), $where = array())
	{
		$qtdWhere = count($where);
		$i = 1;

		if ($qtdWhere == 0)
		{
			throw new Exception('Erro ao atualizar dados: é necessário uma cláusula WHERE');
			return false;
		}

		$w = '';

		foreach ($where as $key => $value) {

			if ($i == $qtdWhere)
			{
				$w .= " {$key} = '{$value}' ";
			}
			else
			{
				$w .= " {$key} = '{$value}' AND ";
			}

			$i++;
		}

		foreach ($values as $key => $value) {
			$this->query("UPDATE {$this->table} SET {$key} = '{$value}' WHERE {$w}");
		}
	}

	public function listAll($table)
	{
		return $this->getList("SELECT * FROM $table");
	}

	public function delete($id)
	{
		$this->query("DELETE FROM {$this->table} WHERE {$this->primary_key} = '$id'");
	}

	public function searchById($table, $primary_key, $id)
	{
		return $this->getRow("SELECT * FROM $table WHERE $primary_key = '$id'");
	}

}