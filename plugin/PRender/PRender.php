<?php
/*
 * Render渲染器
 * SP::PRender($title, 'layout:dir/page', $argument);
 */
class PRender extends Plugin
{
	private $__config;
	private $__theme;
	private $__layout;
	private $__dir;
	private $__page;
	private $__content;
	private $__title;
	
	/**
	 * 构造函数, 读取配置初始化
	 * @param array $config 配置数组
	 */
	function __construct($config)
	{
		$defaultConfig = array('theme' => 'default', 'layout' => 'default');
		if(!is_array($config)) $config = $defaultConfig;
		$this->__config = $config;
		$this->__config['theme'] = $this->__config['theme'] ? : $defaultConfig['theme'];
		$this->__theme = $this->__config['theme'];
		$this->__config['layout'] = $this->__config['layout'] ? : $defaultConfig['layout'];
	}

	public function invoke($title = '', $path = null, $data = array(), $print = true)
	{
		if(!$print)
			ob_start();

		$this->__title = $title;

		
 		if(IS_AJAX)
		{
			header('Content-Type: application/json');
			echo json_encode($data);
		}
		elseif(is_string($path))
		{
			preg_match('/^((.+):)?((.+)\/)?(.+)?$/', $path, $match);
			$this->__layout = $match[2] ? : $this->__config['layout'];
			$this->__dir = $match[4] ? : strtolower(SP::getController());
			$this->__page = $match[5] ? : strtolower(SP::getAction());
			@extract($data);
			ob_start();
			include APP_ROOT.DS.'protected'.DS.'view'.DS."{$this->__theme}".DS."{$this->__dir}".DS."{$this->__page}.html";
			$content = ob_get_clean();
			include APP_ROOT.DS.'protected'.DS.'view'.DS."{$this->__theme}".DS."{$this->__layout}.html";
		}

		if(!$print)
			$this->__content = ob_get_clean();
	}

	/**
	 * 获取渲染内容, 仅在指定不直接输出的情况下有效
	 * @return string 渲染内容
	 */
	public function getContent()
	{
		return $this->__content;
	}

	public function getTitle()
	{
		return $this->__title;
	}
}
