<?php
/**
* Hook
*/
class Hook
{
	private $__hookEvents = array(
		'onBeforeRequest' => array(),
		'onAfterRequest' => array(),
		'onBeforeRunController' => array(),
		'onAfterRunController' => array(),
		);
	private $__hooks;

	/**
	 * 构造函数注册所有需要的组件
	 * @param SP $sp SP对象
	 */
	function __construct()
	{
		$this->__hooks = is_array(SP::getConfig('components')) ? SP::getConfig('components') : array();
		$this->__loadHookEvents();
	}

	/**
	 * 组件注册方法
	 */
	private function __loadHookEvents()
	{
		foreach ($this->__hooks as $pluginName => $enable) if($enable)
		{
			// 反射出有所方法
			$class = new ReflectionClass($pluginName);
			$methods = $class->getMethods();
			// 注册事件
			foreach ($methods as $m) if(isset($this->__hookEvents[$m->name]))
				$this->__hookEvents[$m->name][] = $m->class;
		}
	}

	/**
	 * 组件调用方法
	 * @param 	string 	$name 		钩子名
	 * @param 	array 	$arguments	参数
	 */
	public function __call($name, $arguments)
	{
		if(isset($this->__hookEvents[$name])) foreach ($this->__hookEvents[$name] as $pluginName)
			call_user_func_array(array(SP::$pluginName(), $name), $arguments);
	}
}
