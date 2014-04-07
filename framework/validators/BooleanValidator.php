<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;

/**
 * BooleanValidator 检查属性值是否为boolean值。
 *
 * 布尔值可以通过[[trueValue]]和[[falseValue]]属性进行配置。
 * 可以和[[strict]]进行比较。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class BooleanValidator extends Validator
{
	/**
	 * @var mixed 代表状态值为true。默认设置为'1'。
	 */
	public $trueValue = '1';
	/**
	 * @var mixed 代表状态值为false。默认设置为'0'。
	 */
	public $falseValue = '0';
	/**
	 * @var boolean 是否严格的等于[[trueValue]]或者[[falseValue]]。
	 * 当此变量为true时，属性值和类型必须都匹配[[trueValue]]或[[falseValue]]。
	 * 默认设置为false，意味着只有该值需要匹配。
	 */
	public $strict = false;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = Yii::t('yii', '{attribute} must be either "{true}" or "{false}".');
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		$valid = !$this->strict && ($value == $this->trueValue || $value == $this->falseValue)
			|| $this->strict && ($value === $this->trueValue || $value === $this->falseValue);
		if (!$valid) {
			return [$this->message, [
				'true' => $this->trueValue,
				'false' => $this->falseValue,
			]];
		} else {
			return null;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		$options = [
			'trueValue' => $this->trueValue,
			'falseValue' => $this->falseValue,
			'message' => Yii::$app->getI18n()->format($this->message, [
				'attribute' => $object->getAttributeLabel($attribute),
				'true' => $this->trueValue,
				'false' => $this->falseValue,
			], Yii::$app->language),
		];
		if ($this->skipOnEmpty) {
			$options['skipOnEmpty'] = 1;
		}
		if ($this->strict) {
			$options['strict'] = 1;
		}

		ValidationAsset::register($view);
		return 'yii.validation.boolean(value, messages, ' . json_encode($options) . ');';
	}
}
