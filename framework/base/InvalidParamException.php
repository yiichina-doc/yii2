<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * InvalidParamException 表示由传递给方法的参数无效导致的异常。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class InvalidParamException extends Exception
{
	/**
	 * @return string 这个异常友好的名称
	 */
	public function getName()
	{
		return 'Invalid Parameter';
	}
}
