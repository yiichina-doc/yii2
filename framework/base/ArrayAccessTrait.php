<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ArrayAccessTrait 提供了 [[\IteratorAggregate]]， [[\ArrayAccess]] 和 [[\Countable]] 的实现。
 *
 * 需要注意的是ArrayAccessTrait需要类使用它包含一个名为 `data` 的属性，它应该是一个数组。
 * 这些数据将被ArrayAccessTrait显现，以支持像访问数组的类对象。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
trait ArrayAccessTrait
{
	/**
	 * 返回一个遍历数据的迭代器。
	 * 这个方法需要SPL接口 `IteratorAggregate`.
	 * 它会在您使用 `foreach` 遍历集合时隐式调用。
	 * @return \ArrayIterator 遍历Cookie到集合中的迭代器。
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->data);
	}

	/**
	 * 返回数据元素的数量
	 * 此方法需要通过Countable接口。
	 * @return integer 数据元素的数量。
	 */
	public function count()
	{
		return count($this->data);
	}

	/**
	 * 此方法需要通过接口ArrayAccess。
	 * @param mixed $offset 检索的偏移量
	 * @return boolean
	 */
	public function offsetExists($offset)
	{
		return isset($this->data[$offset]);
	}

	/**
	 * 此方法需要通过接口ArrayAccess。
	 * @param integer $offset 检索元素的偏移量
	 * @return mixed 此偏移量的元素，如果在此偏移量没有找到元素返回null
	 */
	public function offsetGet($offset)
	{
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}

	/**
	 * 此方法需要通过接口ArrayAccess。
	 * @param integer $offset 设置元素的偏移量
	 * @param mixed $item 元素的值
	 */
	public function offsetSet($offset, $item)
	{
		$this->data[$offset] = $item;
	}

	/**
	 * 此方法需要通过接口ArrayAccess。
	 * @param mixed $offset 取消设置元素的偏移量
	 */
	public function offsetUnset($offset)
	{
		unset($this->data[$offset]);
	}
}
