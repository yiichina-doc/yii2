<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\db\ActiveRecordInterface;

/**
 * UniqueValidator 验证该属性值在指定的数据库表是唯一的。
 *
 * UniqueValidator 检查被校验的值在ActiveRecord类[[targetClass]]和[[targetAttribute]]
 * 属性指定的表和字段中是唯一的。
 *
 * 以下是使用此验证程序的验证规则的例子：
 *
 * ```php
 * // a1 needs to be unique
 * ['a1', 'unique']
 * // a1 needs to be unique, but column a2 will be used to check the uniqueness of the a1 value
 * ['a1', 'unique', 'targetAttribute' => 'a2']
 * // a1 and a2 need to unique together, and they both will receive error message
 * [['a1', 'a2'], 'unique', 'targetAttribute' => ['a1', 'a2']]
 * // a1 and a2 need to unique together, only a1 will receive error message
 * ['a1', 'unique', 'targetAttribute' => ['a1', 'a2']]
 * // a1 needs to be unique by checking the uniqueness of both a2 and a3 (using a1 value)
 * ['a1', 'unique', 'targetAttribute' => ['a2', 'a1' => 'a3']]
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class UniqueValidator extends Validator
{
	/**
	 * @var string 该用于验证当前属性值的唯一性的
	 * ActiveRecord类的名称。若未设置。将使用被验证的ActiveRecord类的属性。
	 * @see targetAttribute
	 */
	public $targetClass;
	/**
	 * @var string|array 用于验证当前属性值的唯一性的
	 * ActiveRecord的属性的名称。若未设置，将使用当前
	 * 正在验证的属性名称。可以同时使用数组来验证
	 * 多列的唯一性。数组的值是将被用于
	 * 验证唯一性的属性，数组keys是被验证的属性值。
	 * 如果key和value是相同的，你可以只指定value。
	 */
	public $targetAttribute;
	/**
	 * @var string|array|\Closure 应用在数据库查询上的附加过滤器，这个过滤器用来检查特性值的唯一性。
	 * 这可以是一个字符串或代表附加查询条件的数组 （参考[[\yii\db\Query::where()]]
	 * 查询条件的格式），或以下结构的匿名函数 `function ($query)`, 其中 `$query`
	 * 是可以在 [[\yii\db\Query|Query]] 中修改的函数。
	 */
	public $filter;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = Yii::t('yii', '{attribute} "{value}" has already been taken.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
		/** @var ActiveRecordInterface $targetClass */
		$targetClass = $this->targetClass === null ? get_class($object) : $this->targetClass;
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

		$query = $targetClass::find();
		$query->where($params);

		if ($this->filter instanceof \Closure) {
			call_user_func($this->filter, $query);
		} elseif ($this->filter !== null) {
			$query->andWhere($this->filter);
		}

		if (!$object instanceof ActiveRecordInterface || $object->getIsNewRecord()) {
			// if current $object isn't in the database yet then it's OK just to call exists()
			$exists = $query->exists();
		} else {
			// if current $object is in the database already we can't use exists()
			/** @var ActiveRecordInterface[] $objects */
			$objects = $query->limit(2)->all();
			$n = count($objects);
			if ($n === 1) {
				$keys = array_keys($params);
				$pks = $targetClass::primaryKey();
				sort($keys);
				sort($pks);
				if ($keys === $pks) {
					// primary key is modified and not unique
					$exists = $object->getOldPrimaryKey() != $object->getPrimaryKey();
				} else {
					// non-primary key, need to exclude the current record based on PK
					$exists = $objects[0]->getPrimaryKey() != $object->getOldPrimaryKey();
				}
			} else {
				$exists = $n > 1;
			}
		}

		if ($exists) {
			$this->addError($object, $attribute, $this->message);
		}
	}
}
