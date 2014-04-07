<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

/**
 * DefaultValueValidator 设置该属性为指定默认值。
 *
 * DefaultValueValidator 是一个真正的验证。它主要为了当它们为空时
 * 让指定属性有默认值。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DefaultValueValidator extends Validator
{
	/**
	 * @var mixed 指定的属性设置默认值。
	 */
	public $value;
	/**
	 * @var boolean 此属性的值为false，当此值为空时
	 * 将调用validated。
	 */
	public $skipOnEmpty = false;

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
		if ($this->isEmpty($object->$attribute)) {
			$object->$attribute = $this->value;
		}
	}
}
