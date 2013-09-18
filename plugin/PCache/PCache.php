<?php
/**
* PCache
*/
class PCache extends Plugin implements ICache
{
	private $__cache;
	private $__cacheType;
	private $__config;

	/**
	 * 构造函数, 读取配置初始化
	 * @param array $config 配置数组
	 */
	function __construct($config)
	{
		if(!is_array($config))
			$this->__config = array('type' => 'PFileCache');
		else
			$this->__config = $config;
		if(!isset($this->__config['type'])) $this->__config['type'] = 'PFileCache';
		$cacheType = $this->__cacheType = $this->__config['type'];
		$this->__cache[$this->__cacheType] = SP::$cacheType();
	}

	/**
	 * 为其他原生操作提供支持
	 * @param  string 	$name      	方法名
	 * @param  array 	$arguments 	参数
	 * @return mix      			返回原生方法的返回值
	 */
	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->__cache[$this->__cacheType], $name), $arguments);
	}

	// 实现ICache接口
	/**
	 * 设置缓存
	 * @param string  $key    键
	 * @param string  $value  值
	 * @param integer $expire 过期时间, 0为永不过期
	 */
	public function set($key, $value, $expire = 0)
	{
		return $this->__cache[$this->__cacheType]->set($key, $value, $expire);
	}
	/**
	 * 读取缓存
	 * @param  string $key 键
	 * @return string      值
	 */
	public function get($key)
	{
		return $this->__cache[$this->__cacheType]->get($key);
	}

	/**
	 * 删除一条缓存
	 * @param  string $key 	键
	 * @return boolean     	执行成功true/执行失败false
	 */
	public function del($key)
	{
		return $this->__cache[$this->__cacheType]->del($key);
	}

	/**
	 * 默认调用方法  可以通过SP::PCache($cacheType)方式调用
	 * @param  string $cacheType 缓存类型
	 * @return PCache            PCache对象
	 */
	public function invoke($cacheType = null)
	{
		if($cacheType !== null)
			$this->__cacheType = $cacheType;
		else
			$this->__cacheType = $cacheType = $this->__config['type'];
		if(!isset($this->__cache[$cacheType]))
			$this->__cache[$cacheType] = SP::$cacheType();
	}

	/**
	 * 获取当前缓存类型
	 * @return string 缓存类型
	 */
	public function getCacheType()
	{
		return $this->__cacheType;
	}
}
