<?php
/**
 * 缓存接口, 为了可以通过PCache通用的使用必须实现以下3个方法
 */
interface ICache
{
    public function set($key, $value, $expire);
    public function get($key);
    public function del($key);
}

abstract class Plugin
{
}
