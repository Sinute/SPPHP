<?php
/**
* PLog
*/
class PLog extends Plugin
{
	private $__file;
	private $__timeFormat;
	/**
	 * 构造函数, 读取配置初始化
	 * @param array $config 配置数组
	 */
	function __construct($config)
	{
		$defaultConfig = array('file' => SP::getContentPath().DS.'log'.DS.date('Ym').DS.date('d').'.log', 'timeFormat' => 'Y-m-d H:i:s');
		if(!is_array($config)) $config = $defaultConfig;
		$this->__file = isset($config['file']) ? $config['file'] : $defaultConfig['file'];
		$this->__timeFormat = isset($config['timeFormat']) ? $config['timeFormat'] : $defaultConfig['timeFormat'];
		if(!is_dir(dirname($this->__file)))
			mkdir(dirname($this->__file), 0777, true);
	}

	public function invoke($message)
	{
		$formatMessage = '['.date($this->__timeFormat)."] {$message}".PHP_EOL;
		if(IS_CLI)
			echo $formatMessage;
		file_put_contents($this->__file, $formatMessage, FILE_APPEND);
	}
}
