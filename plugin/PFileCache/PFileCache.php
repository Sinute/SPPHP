<?php
/**
* PFileCache
*/
class PFileCache extends Plugin implements ICache
{
	private $__cacheData;
	private $__path;
	private $__autoMkdir;
	private $__dataPath;

	/**
	 * 构造函数, 读取配置初始化
	 * @param array $config 配置数组
	 */
	function __construct($config)
	{
		$defaultConfig = array('path' => SP::getContentPath(), 'autoMkdir' => true);
		if(!is_array($config)) $config = $defaultConfig;
		if(isset($config['path']))
			$this->__path = rtrim($config['path'], DS).DS;
		else
			$this->__path = $defaultConfig['path'];
		$this->__autoMkdir = isset($config['autoMkdir']) ? $config['autoMkdir'] : $defaultConfig['autoMkdir'];
		$this->__dataPath = $this->__path . 'data.bin';
		if(file_exists($this->__dataPath))
			$this->__cacheData = unserialize(file_get_contents($this->__dataPath));
		else
			$this->__cacheData = array();
	}

	/**
	 * 保存一个文件缓存
	 * @param  string $fileName 	文件名
	 * @param  string $content  	内容
	 * @param  string $path     	路径
	 * @return integer           	返回写入的字节数, 写入失败时返回false
	 */
	public function saveFile($fileName, $content, $path = '')
	{
		$path = $this->__path . trim($path, '\/.') . DS . $fileName;
		if($this->__autoMkdir && !is_dir(dirname($path)))
			mkdir(dirname($path), 0777, true);
		return file_put_contents($path, $content);
	}

	/**
	 * 获取文件缓存
	 * @param  string $fileName 文件名
	 * @param  string $path     路径
	 * @return string           返回文件内容, 读取失败时返回false
	 */
	public function getFile($fileName, $path = '')
	{
		$path = $this->__path . trim($path, '\/.') . DS . $fileName;
		if(file_exists($path))
			return file_get_contents($path);
		return false;
	}

	/**
	 * 获取文件路径
	 * @param  string $fileName 文件名
	 * @param  string $path     路径
	 * @return string           文件路径
	 */
	public function getPath($fileName, $path = '')
	{
		return $path = $this->__path . trim($path, '\/.') . DS . $fileName;
	}

	/**
	 * 删除文件缓存
	 * @param  string $fileName 	文件名
	 * @param  string $path     	路径
	 * @return boolean           	删除成功返回true, 失败返回false
	 */
	public function delFile($fileName, $path = '')
	{
		$path = $this->__path . trim($path, '\/.') . DS . $fileName;
		if(file_exists($path))
			return unlink($path);
		return false;
	}

	/**
	 * 设置缓存
	 * @param string  $key    键
	 * @param string  $value  值
	 * @param integer $expire 过期时间, 0为永不过期, 默认为0
	 */
	public function set($key, $value, $expire = 0)
	{
		if(!$expire)
			$expire = 0;
		else
			$expire += time();
		$this->__cacheData[$key] = array('value' => $value, 'expire' => $expire);
		file_put_contents($this->__dataPath, serialize($this->__cacheData));
	}

	/**
	 * 获取缓存
	 * @param  string $key 键
	 * @return string      值
	 */
	public function get($key)
	{
		if(isset($this->__cacheData[$key])) {
			if($this->__cacheData[$key]['expire'] == 0 || $this->__cacheData[$key]['expire'] > time())
				return $this->__cacheData[$key]['value'];
			else
				unset($this->__cacheData[$key]);
		}
		return null;
	}

	/**
	 * 删除缓存
	 * @param  string $key 	键
	 * @return boolean      总是返回true
	 */
	public function del($key)
	{
		unset($this->__cacheData[$key]);
		file_put_contents($this->__dataPath, serialize($this->__cacheData));
		return true;
	}
}
