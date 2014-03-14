<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use DateTime;

/**
 * DateValidator 验证如果此属性表示适当格式的date, time or datetime。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DateValidator extends Validator
{
	/**
	 * @var string 日期格式的值应遵循被验证过的。
	 * 请参阅<http://www.php.net/manual/en/datetime.createfromformat.php>
	 * 中支持的格式。
	 */
	public $format = 'Y-m-d';
	/**
	 * @var string 得到的解析结果的属性。
	 * 当该属性不为null并且验证成功，已命名的属性
	 * 将获得解析结果。
	 */
	public $timestampAttribute;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = Yii::t('yii', 'The format of {attribute} is invalid.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;
		$result = $this->validateValue($value);
		if (!empty($result)) {
			$this->addError($object, $attribute, $result[0], $result[1]);
		} elseif ($this->timestampAttribute !== null) {
			$date = DateTime::createFromFormat($this->format, $value);
			$object->{$this->timestampAttribute} = $date->getTimestamp();
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
		$date = DateTime::createFromFormat($this->format, $value);
		$errors = DateTime::getLastErrors();
		$invalid = $date === false || $errors['error_count'] || $errors['warning_count'];
		return $invalid ? [$this->message, []] : null;
	}
}
