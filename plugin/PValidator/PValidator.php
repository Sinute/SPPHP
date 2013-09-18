<?php
/**
* PValidator
* 输入验证类
*/
class PValidator extends Plugin
{
	private $__var;
	private $__vars;

	function __construct($config)
	{
	}

	/**
	 * 读入需要验证的属性
	 */
	public function invoke()
	{
		$this->__vars = func_get_args();
	}

	/**
	 * 验证方法
	 * @param  string 	$method    	验证方法
	 * @param  array 	$params    	参数
	 * @param  string 	$msg       	验证失败是返回的信息
	 * @param  integer 	$must 		为空时是否验证, 默认全部验证
	 */
	public function check($method, $params, $msg, $code = 0, $must = true)
	{
		$method = "__{$method}";
		foreach ($this->__vars as $var) {
			$this->__var = $var;
			if($must === false && $this->__var === null) continue;
			if(method_exists($this, $method) && $this->$method($params)) continue;
			throw new HttpException($msg, $code);
		}
		return $this;
	}

	/**
	 * 判断值是否在一个范围中
	 * @param  array  $array 	范围数组
	 * @return boolean        	存在返回true否则返回false
	 */
	private function __in(array $array)
	{
		return in_array($this->__var, $array);
	}

	/**
	 * 值对比方法
	 * @param  array  $compareArray 	对比数组
	 * @return boolean               	所有都对比成功返回true否则false
	 */
	private function __compare(array $compareArray)
	{
		if(isset($compareArray['<']) && $this->__var >= $compareArray['<']) return false;
		if(isset($compareArray['<=']) && $this->__var > $compareArray['<=']) return false;
		if(isset($compareArray['>']) && $this->__var <= $compareArray['>']) return false;
		if(isset($compareArray['>=']) && $this->__var < $compareArray['>=']) return false;
		if(isset($compareArray['=']) && $this->__var != $compareArray['=']) return false;
		if(isset($compareArray['==']) && $this->__var != $compareArray['==']) return false;
		if(isset($compareArray['!=']) && $this->__var == $compareArray['!=']) return false;
		if(isset($compareArray['===']) && $this->__var !== $compareArray['===']) return false;
		if(isset($compareArray['!==']) && $this->__var === $compareArray['!==']) return false;
		return true;
	}

	/**
	 * 判断邮件格式
	 * @return boolean 合法邮箱格式返回true否则false
	 */
	private function __email()
	{
		return preg_match('/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9_.-]+\.[a-zA-Z]+$/', $this->__var);
	}

	/**
	 * 验证字符串长度
	 * @param  array  $lengthArray 	长度对比数组
	 * @return boolean              符合返回true否则false
	 */
	private function __length(array $lengthArray)
	{
		if(is_string($this->__var))
			$this->__var = strlen($this->__var);
		else
			return false;
		return $this->__compare($lengthArray);
	}

	/**
	 * 验证数组大小
	 * @param  array  $lengthArray 	长度对比数组
	 * @return boolean              符合返回true否则false
	 */
	private function __size(array $lengthArray)
	{
		if(is_array($this->__var))
			$this->__var = count($this->__var);
		else
			return false;
		return $this->__compare($lengthArray);
	}

	/**
	 * 验证正则匹配
	 * @param  string $pattern 	正则
	 * @return boolean          匹配返回true/失败返回false
	 */
	private function __match($pattern)
	{
		return (boolean)preg_match($pattern, $this->__var);
	}

	/**
	 * 验证是否为有值
	 * @return boolean 仅当值为null时返回false
	 */
	private function __required()
	{
		return $this->__var !== null;
	}

	/**
	 * 类型验证
	 * @param  string $type 	判断类型
	 * @return boolean       	验证成功返回true否则false
	 */
	private function __type($type)
	{
		$type = strtolower($type);
		if($type === 'int' || $type === 'integer') // 整数
			return (boolean)preg_match('/^[-+]?[0-9]+$/', $this->__var);
		elseif($type === 'numeric') // 数字
			return (boolean)preg_match('/^[-+]?([0-9]*\.)?[0-9]+([eE][-+]?[0-9]+)?$/', $this->__var);
		elseif($type === 'nature') // 自然数
			return (boolean)preg_match('/^[0-9]+$/', $this->__var);
		elseif($type === 'array') // 数组
			return is_array($this->__var);
		return false;
	}
}
