<?php
/**
* 数据库操作类
*/
class PDB extends Plugin
{
	private $__pdo;				// pdo对象
	private $__config;			// 数据库配置
	private $__db;				// 数据库名
	private $__addCache;		// 使用缓存标记
	private $__delCache;		// 清除缓存标记
	private $__assocField;		// 关联字段

	/**
	 * 构造函数, 读取配置初始化
	 * @param array $config 配置数组
	 */
	function __construct($config)
	{
		if(!is_array($config)) return false;
		$this->__config = $config;
		$this->__addCache = false;
		$this->__delCache = false;
		// 未设定默认数据库的情况下取第一个数据库
		if(!isset($config['defaultDB']))
			list($this->__config['defaultDB']) = each($config['dbs']);
		else
			$this->__config['defaultDB'] = $config['defaultDB'];
		$this->invoke($this->__config['defaultDB']);
	}

	/**
	 * 切换数据库
	 * @param  string $db 数据库名
	 */
	public function invoke($db = null)
	{
		if($db !== null)
			$this->__db = $db;
		else
			$this->__db = $this->__config['defaultDB'];
	}

	/**
	 * 连接数据库
	 */
	private function __connect()
	{
		if(!isset($this->__pdo[$this->__db]))
		{
			$this->__pdo[$this->__db] = new PDO(
				$this->__config['dbs'][$this->__db]['dsn'],
				$this->__config['dbs'][$this->__db]['username'],
				$this->__config['dbs'][$this->__db]['password']
				);
			if(isset($this->__config['dbs'][$this->__db]['emulatePrepare']))
				$this->__pdo[$this->__db]->setAttribute(PDO::ATTR_EMULATE_PREPARES, !!$this->__config['dbs'][$this->__db]['emulatePrepare']);
		}
	}

	/**
	 * 复原一次性参数
	 */
	private function __recoverParams()
	{
		$this->__delCache = false;
		$this->__addCache = false;
		$this->__assocField = false;
	}

	/**
	 * sql执行入口
	 * @param  string $sql           	sql查询语句
	 * @param  mix $params        		参数, 单个参数可以为字符串, 多参数为数组
	 * @param  string $fetchFunction 	需要最终调用的方法
	 * @param  mix $fetchParam    		最终调用方法所需的参数
	 * @return boolean                	当有自增时返回自增值, 否则成功true/失败false
	 */
	private function __execute($sql, $params, $fetchFunction = null, $fetchParam = null)
	{
		if($this->__delCache || $this->__addCache !== false)
		{
			$key = md5(serialize($this->__config['dbs'][$this->__db]).$this->__assocField.$sql.serialize($params).$fetchFunction.serialize($fetchParam));
			if($this->__delCache)
			{
				$this->__recoverParams();
				return SP::PCache()->del($key);
			}
			elseif($this->__addCache !== false)
			{
				if($result = SP::PCache()->get($key))
				{
					$this->__recoverParams();
					return unserialize($result);
				}
			}
		}

		$this->__connect();

		if(!is_array($params)) $params = array($params);
		$sth = $this->__pdo[$this->__db]->prepare($sql);
		$result = $sth->execute($params);
		$lastInsertId = $this->lastInsertId();
		if($fetchFunction)
		{
			$result = $sth->$fetchFunction($fetchParam);
			$sth->closeCursor();
			if($this->__assocField && is_array($result))
			{
				$assocResult = array();
				foreach ($result as $row)
					$assocResult[$row[$this->__assocField]] = $row;
				$result = $assocResult;
			}
			if($this->__addCache !== false)
			{
				SP::PCache()->set($key, serialize($result), $this->__addCache);
				$this->__addCache = false;
			}
		}
		$this->__recoverParams();
		if($lastInsertId) return $lastInsertId;
		return $result;
	}

	/**
	 * 获取一行数据
	 * @param  string $sql        	查询语句
	 * @param  array  $params     	参数
	 * @param  int $fetchStyle 		查询方式
	 * @return boolean            	成功true/失败false
	 */
	public function getRow($sql, $params = array(), $fetchStyle = PDO::FETCH_ASSOC)
	{
		return $this->__execute($sql, $params, 'fetch', $fetchStyle);
	}

	/**
	 * 获取所有数据
	 * @param  string $sql        	查询语句
	 * @param  array  $params     	参数
	 * @param  int $fetchStyle 		查询方式
	 * @return boolean             	成功true/失败false
	 */
	public function getAll($sql, $params = array(), $fetchStyle = PDO::FETCH_ASSOC)
	{
		return $this->__execute($sql, $params, 'fetchAll', $fetchStyle);
	}

	/**
	 * 获取单个数据
	 * @param  string $sql    		查询语句
	 * @param  array  $params 		参数
	 * @return boolean	         	成功true/失败false
	 */
	public function getOne($sql, $params = array())
	{
		$result = $this->getRow($sql, $params, PDO::FETCH_NUM);
		return $result[0];
	}

	/**
	 * 通用查询方法
	 * @param  string $sql 		sql查询语句
	 * @param  array $params 	参数
	 * @return boolean 			成功true/失败false
	 */
	public function execute($sql, $params = null)
	{
		return $this->__execute($sql, $params);
	}

	/**
	 * 插入语句模板
	 * @param  string  $table        表名
	 * @param  array   $rows         需要插入的数据
	 * @param  array   $updateFields 重复时需要更新的字段
	 * @param  boolean $skip         重复时是否跳过
	 * @return boolean               执行成功true/执行失败false
	 */
	private function __create($table, array $rows, $updateFields = null, $skip = false)
	{
		$params = array();
		$updateQuery = '';
		$skipQuery = '';
		$fields = implode('`,`', array_keys($rows[0]));
		$values = '';
		$params = array();
		foreach ($rows as $i => $row)
		{
			$values .= '(';
			foreach ($row as $key => $value)
			{
				$f = ":{$key}{$i}";
				$values .= "{$f},";
				$params[$f] = $value;
			}
			$values = rtrim($values, ',') . '),';
		}
		$values = rtrim($values, ',');
		if(is_array($updateFields)) {
			$updateQuery = "ON DUPLICATE KEY UPDATE ";
			if(!$updateFields) $updateFields = array_keys($rows[0]);
			foreach ($updateFields as $field) {
				$updateQuery .= "`{$field}`=VALUES(`{$field}`),";
			}
			$updateQuery = rtrim($updateQuery, ',');
		}elseif($skip) {
			$skipQuery = ' IGNORE ';
		}
		return $this->__execute("INSERT {$skipQuery} INTO `{$table}`(`{$fields}`) VALUES{$values} {$updateQuery}", $params);
	}

	/**
	 * 判断是否为关联数组
	 * @param  array  $array 需要判断的数组
	 * @return boolean       是true/否false
	 */
	private function __isAssoc(array $array)
	{
		$keys = array_keys($array);
		return $keys !== array_keys($keys);
	}

	/**
	 * 插入记录
	 * @param  string $table 	表名
	 * @param  array $rows  	插入多行时数组里面的每个值为一个关联数组, 键值对应到数据库字段名
	 *                       	其中每个关联数组的结构必须一致
	 *                        	例: $rows = array(
	 *                      	  	array('field1'=>'vaule11','field2'=>'vaule12'),
	 *                      	   	array('field1'=>'vaule21','field2'=>'vaule22'),
	 *                      	    	array('field1'=>'vaule31','field2'=>'vaule32'),
	 *                          );
	 * @return boolean        	成功true/失败false
	 */
	public function insert($table, array $rows)
	{
		if($this->__isAssoc($rows))
			$rows = array($rows);
		return $this->__create($table, $rows);
	}

	/**
	 * 插入或更新
	 * @param  string $table        表名
	 * @param  array  $rows         需要插入的数据
	 * @param  array  $updateFields 重复时需要更新的字段
	 * @return boolean              成功true/失败false
	 */
	public function insertOrUpdate($table, array $rows, $updateFields = array())
	{
		if($this->__isAssoc($rows))
			$rows = array($rows);
		return $this->__create($table, $rows, $updateFields);
	}

	/**
	 * 插入或跳过
	 * @param  string $table 	表名
	 * @param  array  $rows  	需要插入的数据
	 * @return boolean        	成功true/失败false
	 */
	public function insertOrSkip($table, array $rows)
	{
		if($this->__isAssoc($rows))
			$rows = array($rows);
		return $this->__create($table, $rows, false, true);
	}

	/**
	 * 在下一条语句中使用缓存
	 * @param  integer $expire 	缓存/false清除缓存
	 * @return PDB 				返回自身
	 */
	public function cache($expire = 0)
	{
		if($expire === 0 || $expire > 0)
		{
			$this->__addCache = $expire;
			$this->__delCache = false;
		}
		else
		{
			$this->__addCache = false;
			$this->__delCache = true;
		}
		return $this;
	}

	public function setAssoc($field)
	{
		$this->__assocField = $field;
		return $this;
	}

	/**
	 * 提供其他原生操作支持
	 */
	public function __call($name, $arguments)
	{
		$this->__connect();
		return call_user_func_array(array($this->__pdo[$this->__db], $name), $arguments);
	}

	// TODO wherein query
}