<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\base\InvalidConfigException;

/**
 * ExistValidator 验证该属性值在表中是否存在。
 *
 * ExistValidator 检查 被校验的值是否存在于
 * ActiveRecord类[[targetClass]]和[[targetAttribute]]属性指定的那个表和列中。
 *
 * 此验证程序通常用来验证一个包含一个值的外键
 * 可以在外部表中找到。
 *
 * 以下是使用此验证程序的验证规则的例子:
 *
 * ```php
 * // a1 needs to exist
 * ['a1', 'exist']
 * // a1 needs to exist, but its value will use a2 to check for the existence
 * ['a1', 'exist', 'targetAttribute' => 'a2']
 * // a1 and a2 need to exist together, and they both will receive error message
 * [['a1', 'a2'], 'exist', 'targetAttribute' => ['a1', 'a2']]
 * // a1 and a2 need to exist together, only a1 will receive error message
 * ['a1', 'exist', 'targetAttribute' => ['a1', 'a2']]
 * // a1 needs to exist by checking the existence of both a2 and a3 (using a1 value)
 * ['a1', 'exist', 'targetAttribute' => ['a2', 'a1' => 'a3']]
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ExistValidator extends Validator
{
	/**
	 * @var string 用来验证当前属性值是否存在的
	 * ActiveRecord类的名字。若未设置，将作用被验证过的ActiveRecord类的属性。
	 * @see targetAttribute
	 */
	public $targetClass;
	/**
	 * @var string|array ActiveRecord的属性的名称是否存在
	 * 于当前属性的值中。 若未设置，将使用
	 * 当前正在验证的属性名称。 可以使用数组来验证
	 * 多列同时存在的问题。 数组值的属性
	 * 用于验证是否存在，数组keys是被验证过的属性值。
	 * 如果该key和value是相同的，你可以指定值。
	 */
	public $targetAttribute;
	/**
	 * @var string|array|\Closure 应用到DB查询的其他过滤器用来检查属性值是否存在。
	 * 可以是一个字符串或表示附加查询条件的数组 (参考[[\yii\db\Query::where()]]
	 * 的查询条件的格式)，或者是一个有 `function ($query)`签名的匿名函数，其中`$query`
	 * 是你可以在函数中修改的[[\yii\db\Query|Query]]对象。
	 */
	public $filter;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = Yii::t('yii', '{attribute} is invalid.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
		$targetAttribute = $this->targetAttribute === null ? $attribute : $this->targetAttribute;

		if (is_array($targetAttribute)) {
			$params = [];
			foreach ($targetAttribute as $k => $v) {
				$params[$v] = is_integer($k) ? $object->$v : $object->$k;
			}
		} else {
			$params = [$targetAttribute => $object->$attribute];
		}

		foreach ($params as $value) {
			if (is_array($value)) {
				$this->addError($object, $attribute, Yii::t('yii', '{attribute} is invalid.'));
				return;
			}
		}

		$targetClass = $this->targetClass === null ? get_class($object) : $this->targetClass;
		$query = $this->createQuery($targetClass, $params);

		if (!$query->exists()) {
			$this->addError($object, $attribute, $this->message);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		if (is_array($value)) {
			return [$this->message, []];
		}
		if ($this->targetClass === null) {
			throw new InvalidConfigException('The "targetClass" property must be set.');
		}
		if (!is_string($this->targetAttribute)) {
			throw new InvalidConfigException('The "targetAttribute" property must be configured as a string.');
		}

		$query = $this->createQuery($this->targetClass, [$this->targetAttribute => $value]);

		return $query->exists() ? null : [$this->message, []];
	}

	/**
	 * 用给定的条件创建一个查询实例。
	 * @param string $targetClass 目标AR类
	 * @param mixed $condition 查询条件
	 * @return \yii\db\ActiveQueryInterface 查询实例
	 */
	protected function createQuery($targetClass, $condition)
	{
		/** @var \yii\db\ActiveRecordInterface $targetClass */
		$query = $targetClass::find()->where($condition);
		if ($this->filter instanceof \Closure) {
			call_user_func($this->filter, $query);
		} elseif ($this->filter !== null) {
			$query->andWhere($this->filter);
		}
		return $query;
	}
}
