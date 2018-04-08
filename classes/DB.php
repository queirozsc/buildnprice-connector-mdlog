<?php
class DB {
	private static $_instance = null, $_dbtype = '';
	private $_oracledb = array("mdlogp");
	private $_pdo, 
		$_query, 
		$_error = false, 
		$_results, 
		$_count = 0,
		$_tns;

	private function __construct($dbtype='mysql'){
		try {
			if ($dbtype == 'mysql') {
				$this->_pdo = new PDO(
					$dbtype.':host='.Config::get($dbtype.'/host').';dbname='.Config::get($dbtype.'/db'),
					Config::get($dbtype.'/user'),
					Config::get($dbtype.'/password'));
			} elseif (in_array($dbtype, $this->_oracledb)) {	
				$tns  = '(DESCRIPTION=(';
				$tns .= 'ADDRESS_LIST=(ADDRESS=(PROTOCOL = TCP)';
				$tns .= '(HOST = '.Config::get($dbtype.'/host').')(PORT = 1521)))';
				$tns .= '(CONNECT_DATA=(SERVICE_NAME = '.Config::get($dbtype.'/db').')))';
				$this->_pdo = new PDO(
					"oci:dbname=".$tns.";charset=AL32UTF8;",	// 
					Config::get($dbtype.'/user'),
					Config::get($dbtype.'/password'));
			} 
		} catch(PDOexception $e){
			die($e->getMessage());
		}
	}

	public static function getInstance($dbtype='mysql'){
		if(!isset(self::$_instance) || self::$_dbtype != $dbtype){
			self::$_instance = new DB($dbtype);
			self::$_dbtype   = $dbtype;
		}
		return self::$_instance;
	}

	public function query($sql, $params = array()){
		$this->_error   = false;
		
		if($this->_query = $this->_pdo->prepare($sql)){
			$x = 1;
			if(count($params)){
				foreach ($params as $param) {
					$this->_query->bindValue($x, $param);
					$x++;
				}
			}
			$resultado = $this->_query->execute();
			if ($resultado) {
				$this->_results = $this->_query->fetchAll(PDO::FETCH_OBJ);
				$this->_count   = $this->_query->rowCount(); //count($this->_results);//
			} else {
				$this->_error   = true;
				$this->_results = null;
				$this->_count   = 0;
			}
		}

		return $this;
	}

	public function insert($table, $fields = array()){		
		$keys   = array_keys($fields);
		$values = null;
		$x      = 1;

		foreach ($fields as $field) {
			$values .= "?";
			if($x < count($fields)){
				$values .= ', ';
			}
			$x++;
		}
		if (in_array(self::$_dbtype, $this->_mssqldb)) {
			$sql = "INSERT INTO {$table} (". implode(",", $keys) .") VALUES (".$values.")";
		} else {
			$sql = "INSERT INTO {$table} (`". implode("`,`", $keys) ."`) VALUES (".$values.")";
		}
		if (!$this->query($sql, $fields)->error()) {
			return true;
		}
		return false;
	}

	public function update($table, $id, $fields, $key=null){
		$set = '';
		$x   = 1;

		foreach ($fields as $name => $value) {
			$set .= "{$name} = ?";
			if ($x < count($fields)) {
				$set .= ', ';
			}
			$x++;
		}
		$key = ($key) ? $key : 'id';
		$sql = "UPDATE {$table} SET {$set} WHERE ".$key." = {$id}";
		if (!$this->query($sql, $fields)->error()) {
			return true;
		}

		return false;
	}

	private function action($action, $table, $where = array(), $orderby = ''){
		if(count($where)===3){
			$operators = array('=', '>', '<', '>=', '<=', 'like');

			$field    = $where[0];
			$operator = $where[1];
			$value    = $where[2];

			if(in_array($operator, $operators)){
				$sql = "{$action} FROM {$table} WHERE {$field} {$operator} ? {$orderby}";
				if(!$this->query($sql, array($value))->error()) {
					return $this;
				}
			}
		}
		return false;
	}

	public function get($table, $where, $orderby=''){
		return $this->action('SELECT *', $table, $where, $orderby);
	}

	public function delete($table, $where){
		return $this->action('DELETE', $table, $where);
	}

	public function results(){
		return $this->_results;
	}

	public function first(){
		return ($this->count()) ? $this->results()[0] : null;
	}

	public function last(){
		return ($this->count()) ? $this->results()[$this->count()-1] : null;
	}

	public function error(){
		return $this->_error;
	}

	public function count(){
		return $this->_count;
	}

	public function pdo(){
		return $this->_pdo;
	}

}
