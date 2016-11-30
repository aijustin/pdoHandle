<?php

/**
 * PDO 辅助类
 * @author quqiang
 */

class pdoHandle
{
    private $pdo = NULL;
	private $errorInfo = [];
	private $whereParms = [];
	private $whereSql = NULL;
	private $whereDataType = [];
	private $table = NULL;
	private $limit = '';
	private $order = '';
	private $field = '*';
	
    const PARAM_BOOL = \PDO::PARAM_BOOL;
	const PARAM_NULL = \PDO::PARAM_NULL;
	const PARAM_INT = \PDO::PARAM_INT;
	const PARAM_STR = \PDO::PARAM_STR;
	const PARAM_LOB = \PDO::PARAM_LOB;
	const PARAM_STMT = \PDO::PARAM_STMT;
	
	

    public function __construct($pdoObj)
	{
        $this->pdo = $pdoObj;
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
	
    public function __destruct(){
		if(!empty($this->errorInfo[0]) && $this->errorInfo[0] != '00000'){
	        throw new \Exception(implode('  ', $this->errorInfo));
		}
    }
	
	/**
	 * 获取一条数据
	 *
	 * @param string $sql  数据sql语句
	 * @param string $parms 预处理参数 [':id'=>1]
	 * @param string $dataType 指定预处理参数的数据类型[':id'=>$object::PARAM_INT]
	 * @return array
	 * @author quqiang
	 */
    public function getRow($sql, $parms = [], $dataType = []){
		$stmt = $this->prepareBindParms($sql, $parms, $dataType);
		$stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		return $stmt->fetch(\PDO::FETCH_ASSOC);
    }


	/**
	 * 获取所有数据
	 *
	 * @param string $sql 数据sql语句
	 * @param string $parms 预处理参数 [':id'=>1]
	 * @param string $dataType 指定预处理参数的数据类型[':id'=>$object::PARAM_INT]
	 * @return array
	 * @author quqiang
	 */
    public function getAll($sql, $parms = [], $dataType = []){
		$stmt = $this->prepareBindParms($sql, $parms, $dataType);
		$stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
	

	/**
	 * 执行一段带预处理的SQL
	 *
	 * @param string $sql 数据sql语句
	 * @param string $parms 预处理参数 [':id'=>1]
	 * @param string $dataType 指定预处理参数的数据类型[':id'=>$object::PARAM_INT]
	 * @return bool
	 * @author quqiang
	 */
    public function exec($sql, $parms = [], $dataType = []){
		$stmt = $this->prepareBindParms($sql, $parms, $dataType);
		$status = $stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		return $status;
    }
	
	/**
	 * 获取最后一次写入的ID
	 *
	 * @return int
	 * @author quqiang
	 */
	public function getLastInsertId(){
		return $this->pdo->lastInsertId();
	}
	
	/**
	 * 预处理参数
	 *
	 * @param string $sql 数据sql语句
	 * @param string $parms 预处理参数 [':id'=>1]
	 * @param string $dataType 指定预处理参数的数据类型[':id'=>$object::PARAM_INT]
	 * @return \PDOStatement
	 * @author quqiang
	 */
    private function prepareBindParms($sql, $parms = [], $dataType = []){
        $stmt = $this->pdo->prepare($sql);
		if($stmt === FALSE){
	        throw new \Exception('SQL PrePare ERROR');
		}
		if(empty($parms)){
			return $stmt;
		}
		array_walk($parms, function($v, $k) use($stmt, $dataType){
			$callParms = [];
			$callParms[] = $k;
			$callParms[] = $v;
			if(isset($dataType[$k])){
				$callParms[] = $dataType[$k];
			}
			call_user_func_array([$stmt, 'bindValue'], $callParms);
		});
		return $stmt;
    }
	
	/**
	 * 带有预处理的创建，需要使用前置table方法设置表名
	 *
	 * @param string $parms ['id'=>1,'content'=>'demo content']
	 * @return int
	 * @author quqiang
	 */
    public function create($parms = []){
		if(empty($parms)){
			return false;
		}
		$keys = array_keys($parms);
		$values = array_values($parms);
		$sql = 'INSERT INTO `'.$this->table.'` ('.implode(',', $keys).') VALUES ('.implode(',', array_fill(0, count($keys), '?')).')';
        $stmt = $this->pdo->prepare($sql);
		$stmt->execute($values);
		$this->errorInfo = $stmt->errorInfo();
		return $this->getLastInsertId();
    }
	
	/**
	 * 设置表名称，为下一步执行进行操作
	 *
	 * @param string $table 表名
	 * @return $this
	 * @author quqiang
	 */
	public function table($table)
	{
		$this->table = $table;
		return $this;
	}
	
	/**
	 * 设置一个where条件 
	 *
	 * @param string $whereSql  where后部分的SQl   id=:id
	 * @param string $parms   	条件SQL中的预处理参数 [:id=>1]
	 * @param string $dataType  条件SQL中的预处理参数的数据类型[':id'=>$object::PARAM_INT]
	 * @return $this
	 * @author quqiang
	 */
	public function where($whereSql, $parms = [], $dataType = [])
	{
		$this->whereSql = $whereSql;
		$this->whereParms = $parms;
		$this->whereDataType = $dataType;
		return $this;
	}
	
	
	/**
	 * 更新操作,需要前置条件TABLE方法
	 *
	 * @param string $value 更新的字段名和值 ['name'=>'quqiang']
	 * @return bool
	 * @author quqiang
	 */
    public function update($value){
		$keys = array_keys($value);
		$values = array_values($value);
		$sets = [];
		$newParms = [];
		foreach ($keys as $v) {
			$k = ":__{$v}";
			$sets[] = "`{$v}`={$k}";
			$newParms[$k] = $value[$v];
		}
		$whereSql = '';
		if(!empty($this->whereSql)){
			$whereSql = ' WHERE '. $this->whereSql;
		}
		
		$sql = 'UPDATE '. $this->table .'  SET ' . implode(',', $sets) . $whereSql . $this->order . $this->limit;
		$newParms = array_merge($newParms, $this->whereParms);
		$stmt = $this->prepareBindParms($sql, $newParms, $this->whereDataType);
		$execStatus = $stmt->execute();
		$this->defaultWhere();
		$this->errorInfo = $stmt->errorInfo();
		return $execStatus;
    }

	/**
	 * 删除操作，需要前置条件TABLE
	 *
	 * @return bool
	 * @author quqiang
	 */
    public function delete(){
		$whereSql = '';
		if(!empty($this->whereSql)){
			$whereSql = ' WHERE '. $this->whereSql;
		}
		$sql = 'DELETE FROM '.$this->table. ' ' . $whereSql . $this->order . $this->limit;
		$stmt = $this->prepareBindParms($sql, $this->whereParms, $this->whereDataType);
		$execStatus = $stmt->execute();
		$this->defaultWhere();
		$this->errorInfo = $stmt->errorInfo();
		return $execStatus;
    }
	
	/**
	 * 依据前置条件获取所有数据
	 *
	 * @return array
	 * @author quqiang
	 */
	public function all()
	{
		$whereSql = '';
		if(!empty($this->whereSql)){
			$whereSql = ' WHERE '. $this->whereSql;
		}
		
		$sql = 'SELECT '.$this->field.' FROM '.$this->table. ' ' . $whereSql . $this->order . $this->limit;
		$stmt = $this->prepareBindParms($sql, $this->whereParms, $this->whereDataType);
		$stmt->execute();
		$this->defaultWhere();
		$this->errorInfo = $stmt->errorInfo();
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
	
	/**
	 * 依据前置条件获取一条数据
	 *
	 * @return array
	 * @author quqiang
	 */
	public function find()
	{
		$whereSql = '';
		if(!empty($this->whereSql)){
			$whereSql = ' WHERE '. $this->whereSql;
		}
		
		$sql = 'SELECT '.$this->field.' FROM '.$this->table. ' ' . $whereSql . $this->order . $this->limit;
		$stmt = $this->prepareBindParms($sql, $this->whereParms, $this->whereDataType);
		$stmt->execute();
		$this->defaultWhere();
		$this->errorInfo = $stmt->errorInfo();
		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}
	
	/**
	 * 设置要查询的字段名称
	 *
	 * @param string $field 默认 *
	 * @return $this
	 * @author quqiang
	 */
	public function field($field = '*')
	{
		$this->field = $field;
		return $this;
	}
	
	/**
	 * 设置查询语句的数量条件
	 *
	 * @param string $offset 偏移量
	 * @param string $limit  数量
	 * @return $this
	 * @author quqiang
	 */
	public function limit($offset, $limit = 0)
	{
		if (empty($limit)) {
			$this->limit = " LIMIT {$offset} ";
		} else {
			$this->limit = " LIMIT {$offset},{$limit} ";
		}
		return $this;
	}
	
	/**
	 * 设置排序条件
	 *
	 * @param string $order 排序语句   id DESC
	 * @return $this
	 * @author quqiang
	 */
	public function order($order = '')
	{
		if (!empty($order)) {
			$this->order = ' ORDER BY '.$order.' ';
		}
		return $this;
	}
	
	
	private function defaultWhere()
	{
		$this->whereSql = NULL;
		$this->whereParms = [];
		$this->whereDataType = [];
		$this->table = NULL;
		$this->limit = '';
		$this->order = '';
		$this->field = '*';
		return $this;
	}

	public function getLastErrorInfo()
	{
		return $this->errorInfo;
	}

}
