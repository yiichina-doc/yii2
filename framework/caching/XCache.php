<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

/**
 * XCache 提供XCache cache的应用组件
 *
 * 使用这个应用组件, 必须加载 [XCache PHP extension](http://xcache.lighttpd.net/).
 * 使用[[flush()]] 功能是要注意，PHP.ini中的 "xcache.admin.enable_auth" 只能 "Off".
 *
 * See [[Cache]] for common cache operations that XCache supports.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class XCache extends Cache
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
		return xcache_isset($key);
	}

	/**
	 * 获取指定的键的值.
	 * 这是在父类中定义的方法的具体实现.
	 * @param string $key 一个缓存中唯一的键名
	 * @return string|boolean 缓存中存储的值，如果该值不存在或者已过期则返回false.	
	 */
	protected function getValue($key)
	{
		return xcache_isset($key) ? xcache_get($key) : false;
	}

	/**
	 * 更新缓存中已存在键的值.
	 * 这是在父类中定义的方法的具体实现.
	 *
	 * @param string $key 要更新的键，会检查缓存中是否已有此键
	 * @param string $value 要缓存的值
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return boolean 设置成功返回true,失败返回false
	 */
	protected function setValue($key, $value, $expire)
	{
		return xcache_set($key, $value, $expire);
	}

	/**
	 * 往缓存中新添加一对键值，当新添加的键不存在于缓存中执行.
	 * 这是在父类中定义的方法的具体实现.
	 *
	 * @param string $key 要添加的键，会检查缓存中是否已有此键
	 * @param string $value 要缓存的值
	 * @param integer $expire 缓存过期时间，以秒为单位. 0 代表永不过期.
	 * @return boolean 添加成功返回true,失败返回false
	 */
	protected function addValue($key, $value, $expire)
	{
		return !xcache_isset($key) ? $this->setValue($key, $value, $expire) : false;
	}

	/**
	 * 从缓存中删除指定键的值
	 * 这是父类中定义的方法的具体实现.
	 * @param string $key 要删除值的键值
	 * @return boolean 如果没有错误产生就会执行删除
	 */
	protected function deleteValue($key)
	{
		return xcache_unset($key);
	}

	/**
	 * 清空缓存.
	 * 这是父类中定义的方法的具体实现.
	 * @return boolean 清空是否成功.
	 */
	protected function flushValues()
	{
		for ($i = 0, $max = xcache_count(XC_TYPE_VAR); $i < $max; $i++) {
			if (xcache_clear_cache(XC_TYPE_VAR, $i) === false) {
				return false;
			}
		}
		return true;
	}
}
