<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;

/**
 * RequiredValidator 验证指定的属性不具有null或empty。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class RequiredValidator extends Validator
{
	/**
	 * @var boolean 如果要验证的值是empty是否要跳过这个验证。
	 */
	public $skipOnEmpty = false;
	/**
	 * @var mixed 该属性必须具有所需的值。
	 * 如果此值为null，validator将验证指定的属性不为empty。
	 * 如果设置为一个不为null的值，这个验证器验证
	 * 这个特性和它的属性值相等。
	 * 默认设置为null。
	 * @see strict
	 */
	public $requiredValue;
	/**
	 * @var boolean 属性值和[[requiredValue]]之间的比较是否严格。
	 * 当为true时，两个值和类型必须匹配。
	 * 默认设置为false，意味着只有值需要匹配。
	 * 需要注意的是当[[requiredValue]]为null时，如果此属性为true，validator检查该属性值
	 * 是否为null; 如果此属性为false，validator将调用[[isEmpty]]
	 * 来检查属性值是否为empty。
	 */
	public $strict = false;
	/**
	 * @var string 用户定义的错误消息。它可能包含以下
	 * 将相应的validator来代替的占位符：
	 *
	 * - `{attribute}`: 被验证属性的标签
	 * - `{value}`: 被验证的属性值
	 * - `{requiredValue}`: [[requiredValue]]的值
	 */
	public $message;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = $this->requiredValue === null ? Yii::t('yii', '{attribute} cannot be blank.')
				: Yii::t('yii', '{attribute} must be "{requiredValue}".');
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		if ($this->requiredValue === null) {
			if ($this->strict && $value !== null || !$this->strict && !$this->isEmpty($value, true)) {
				return null;
			}
		} elseif (!$this->strict && $value == $this->requiredValue || $this->strict && $value === $this->requiredValue) {
			return null;
		}
		if ($this->requiredValue === null) {
			return [$this->message, []];
		} else {
			return [$this->message, [
				'requiredValue' => $this->requiredValue,
			]];
		}
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		$options = [];
		if ($this->requiredValue !== null) {
			$options['message'] = Yii::$app->getI18n()->format($this->message, [
				'requiredValue' => $this->requiredValue,
			], Yii::$app->language);
			$options['requiredValue'] = $this->requiredValue;
		} else {
			$options['message'] = $this->message;
		}
		if ($this->strict) {
			$options['strict'] = 1;
		}

		$options['message'] = Yii::$app->getI18n()->format($options['message'], [
			'attribute' => $object->getAttributeLabel($attribute),
		], Yii::$app->language);

		ValidationAsset::register($view);
		return 'yii.validation.required(value, messages, ' . json_encode($options) . ');';
	}
}
