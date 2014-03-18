<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use yii\base\InvalidConfigException;

/**
 * FilterValidator 根据filter的属性转换。
 *
 * FilterValidator 不是验证器，但数据处理器。
 * 它调用指定的filter回调函数来处理属性值，
 * 并保存处理后的值到属性。 该filter必须是一个有效的PHP回调
 * 根据以下结构:
 *
 * ~~~
 * function foo($value) {...return $newValue; }
 * ~~~
 *
 * 许多PHP函数符合这个结构 (例如 `trim()`)。
 *
 * 要指定filter，设置[[filter]]属性为callback。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class FilterValidator extends Validator
{
	/**
	 * @var callable the filter. 以是一个全局函数名称， 匿名函数， 等等。
	 * 函数结构必须按如下方式，
	 *
	 * ~~~
	 * function foo($value) {...return $newValue; }
	 * ~~~
	 */
	public $filter;
	/**
	 * @var boolean 此属性值为假，当值被验证为空时
	 * 此验证器将被应用。
	 */
	public $skipOnEmpty = false;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->filter === null) {
			throw new InvalidConfigException('The "filter" property must be set.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
		$object->$attribute = call_user_func($this->filter, $object->$attribute);
	}
}
