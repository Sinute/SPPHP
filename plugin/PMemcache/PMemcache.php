<?php
/**
* Memcache
*/
class PMemcache extends Plugin implements ICache
{
	private $__memcache;
	private $__compressed;

	/**
	 * 构造函数, 读取配置初始化
	 * @param array $config 配置数组
	 */
	function __construct($config)
	{
		$defaultConfig = array(
			'servers' => array(
				array('localhost', 11211, false, 100),
				),
			'compressed' => false
			);
		if(!is_array($config)) $config = $defaultConfig;
		$this->__memcache = new Memcache;
		$this->__compressed = isset($config['compressed']) ? $config['compressed'] : $defaultConfig['compressed'];
		if(!isset($config['servers'])) $config['servers'] = $defaultConfig['servers'];
		foreach ($config['servers'] as $server)
			call_user_func_array(array($this, 'addServer'), $server);
	}

	/**
	 * 为原生方法提供支持
	 * @param  string 	$name      	方法名
	 * @param  array 	$arguments 	参数
	 * @return mix            		返回原生方法的返回值
	 */
	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->__memcache, $name), $arguments);
	}
	
	/**
	 * 设置缓存
	 * @param string 	$key    键
	 * @param string 	$value  值
	 * @param integer 	$expire 过期时间
	 */
	public function set($key, $value, $expire)
	{
		return $this->__memcache->set($key, $value, $this->__compressed, $expire);
	}

	/**
	 * 获取缓存
	 * @param  string $key 键
	 * @return string      值
	 */
	public function get($key)
	{
		return $this->__memcache->get($key);
	}

	/**
	 * 删除缓存
	 * @param  string $key 	键
	 * @return boolean		成功true/失败false
	 */
	public function del($key)
	{
		return $this->__memcache->delete($key);
	}
}
