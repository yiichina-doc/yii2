<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * InvalidCallException 表示以错误的方式调用一个方法导致的异常。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InvalidCallException extends Exception
{
	/**
	 * @return string 这个异常友好的名称
	 */
	public function getName()
	{
		return 'Invalid Call';
	}
}
