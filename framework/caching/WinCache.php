<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

/**
 * WinCache provides Windows Cache caching in terms of an application component.
 *
 * To use this application component, the [WinCache PHP extension](http://www.iis.net/expand/wincacheforphp)
 * must be loaded. Also note that "wincache.ucenabled" should be set to "On" in your php.ini file.
 *
 * See [[Cache]] manual for common cache operations that are supported by WinCache.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class WinCache extends Cache
{
	/**
	 * 检查一个键在缓存中是否已经存在.
	 * 当缓存中数据特别在时此操作要快与获取.
	 * 此方法不会检查依赖关系 
	 * 缓存的数据发生改变时，get返回false，exits返回true.
	 * @param mixed $key 缓存中值的键. 可以是一个简单的字符串也可以是一个键值的数据结构
	 * @return boolean 存在于缓存中时返回true，如果该值不存在或者已过期则返回false.
	 */
	public function exists($key)
	{
		$key = $this->buildKey($key);
		return wincache_ucache_exists($key);
	}

	/**
	 * 从缓存中获取指定的键的值.
	 * 这是在父类中定义的方法的具体实现.
	 * @param string $key 一个缓存中唯一的键名
	 * @return string|boolean 缓存中存储的值，如果该值不存在或者已过期则返回false.	
	 */
	protected function getValue($key)
	{
		return wincache_ucache_get($key);
	}

	/**
	 * 从缓存中获取多个指定的键的值.
	 * @param array $keys 要检索键列表
	 * @return array 通过指定键找到的在缓存中的值的列表
	 */
	protected function getValues($keys)
	{
		return wincache_ucache_get($keys);
	}

	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key, $value, $expire)
	{
		return wincache_ucache_set($key, $value, $expire);
	}

	/**
	 * Stores multiple key-value pairs in cache.
	 * @param array $data array where key corresponds to cache key while value is the value stored
	 * @param integer $expire the number of seconds in which the cached values will expire. 0 means never expire.
	 * @return array array of failed keys
	 */
	protected function setValues($data, $expire)
	{
		return wincache_ucache_set($data, null, $expire);
	}

	/**
	 * 当添加的值的键在缓存中不存在时，进行缓存.
	 * 这是在父类中定义方法的具体实现.
	 * @param string $key 当键通过检查时进行缓存
	 * @param string $value 要缓存的值
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return boolean 缓存成功返回true，失败返回false
	 */
	protected function addValue($key, $value, $expire)
	{
		return wincache_ucache_add($key, $value, $expire);
	}

	/**
	 * 添加多个键值到缓存.
	 * 此方法默认调用[[addValue()]]去一个个添加来实现. I
	 * 如果缓存支持多个添加, 因利用此特性重写此方法
	 * @param array $data 数组中的键值也将是其值在缓存中对应的键值
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return array 返回缓存失败的键
	 */
	protected function addValues($data, $expire)
	{
		return wincache_ucache_add($data, null, $expire);
	}

	/**
	 * 从缓存中删除指定键的值
	 * 这是父类中定义的方法的具体实现.
	 * @param string $key 要删除值的键值
	 * @return boolean 如果没有错误产生就会执行删除
	 */
	protected function deleteValue($key)
	{
		return wincache_ucache_delete($key);
	}

	/**
	 * 清空缓存.
	 * 这是父类中定义的方法的具体实现.
	 * @return boolean 清空是否成功.
	 */
	protected function flushValues()
	{
		return wincache_ucache_clear();
	}
}
