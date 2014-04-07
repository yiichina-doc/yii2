<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

/**
 * SafeValidator 作为一个虚拟的验证器，它的主要目的是批量赋值时把属性标记为安全的。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class SafeValidator extends Validator
{
	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
	}
}
