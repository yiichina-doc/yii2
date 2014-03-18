<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;

/**
 * StringValidator 验证该属性值长度。
 *
 * 请注意，此验证只能字符串类型的属性使用。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class StringValidator extends Validator
{
	/**
	 * @var integer|array 指定要验证的值的长度限制。
	 * 这可以通过以下形式之一指定：
	 *
	 * - 一个整数：该值应该为确切的长度;
	 * - 一种元素的数组：该值应为最小长度。例如，`[8]`。
	 *   这将覆盖[[min]]。
	 * - 两个元素的数组：该值应该是最小和最大长度。
	 *   例如， `[8, 128]`。 将覆盖[[min]]和[[max]]。
	 */
	public $length;
	/**
	 * @var integer 最大长度。若未设置，意味着没有最大长度限制。
	 */
	public $max;
	/**
	 * @var integer 最小长度。若未设置，意味着没有最小长度限制。
	 */
	public $min;
	/**
	 * @var string 若该值不是一个字符串，使用用户定义的错误消息
	 */
	public $message;
	/**
	 * @var string 当该值长度小于[[min]]时，使用用户定义的错误消息。
	 */
	public $tooShort;
	/**
	 * @var string 当该值长度大于[[max]]时，使用用户定义的错误消息。
	 */
	public $tooLong;
	/**
	 * @var string 当该值的长度不等于[[length]]时，使用用户定义的错误消息。
	 */
	public $notEqual;
	/**
	 * @var string 字符串值被验证的编码 (例如 'UTF-8')。
	 * 若未设置此属性，将使用[[\yii\base\Application::charset]]。
	 */
	public $encoding;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if (is_array($this->length)) {
			if (isset($this->length[0])) {
				$this->min = $this->length[0];
			}
			if (isset($this->length[1])) {
				$this->max = $this->length[1];
			}
			$this->length = null;
		}
		if ($this->encoding === null) {
			$this->encoding = Yii::$app->charset;
		}
		if ($this->message === null) {
			$this->message = Yii::t('yii', '{attribute} must be a string.');
		}
		if ($this->min !== null && $this->tooShort === null) {
			$this->tooShort = Yii::t('yii', '{attribute} should contain at least {min, number} {min, plural, one{character} other{characters}}.');
		}
		if ($this->max !== null && $this->tooLong === null) {
			$this->tooLong = Yii::t('yii', '{attribute} should contain at most {max, number} {max, plural, one{character} other{characters}}.');
		}
		if ($this->length !== null && $this->notEqual === null) {
			$this->notEqual = Yii::t('yii', '{attribute} should contain {length, number} {length, plural, one{character} other{characters}}.');
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
		$value = $object->$attribute;

		if (!is_string($value)) {
			$this->addError($object, $attribute, $this->message);
			return;
		}

		$length = mb_strlen($value, $this->encoding);

		if ($this->min !== null && $length < $this->min) {
			$this->addError($object, $attribute, $this->tooShort, ['min' => $this->min]);
		}
		if ($this->max !== null && $length > $this->max) {
			$this->addError($object, $attribute, $this->tooLong, ['max' => $this->max]);
		}
		if ($this->length !== null && $length !== $this->length) {
			$this->addError($object, $attribute, $this->notEqual, ['length' => $this->length]);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		if (!is_string($value)) {
			return [$this->message, []];
		}

		$length = mb_strlen($value, $this->encoding);

		if ($this->min !== null && $length < $this->min) {
			return [$this->tooShort, ['min' => $this->min]];
		}
		if ($this->max !== null && $length > $this->max) {
			return [$this->tooLong, ['max' => $this->max]];
		}
		if ($this->length !== null && $length !== $this->length) {
			return [$this->notEqual, ['length' => $this->length]];
		}

		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		$label = $object->getAttributeLabel($attribute);

		$options = [
			'message' => Yii::$app->getI18n()->format($this->message, [
				'attribute' => $label,
			], Yii::$app->language),
		];

		if ($this->min !== null) {
			$options['min'] = $this->min;
			$options['tooShort'] = Yii::$app->getI18n()->format($this->tooShort, [
				'attribute' => $label,
				'min' => $this->min,
			], Yii::$app->language);
		}
		if ($this->max !== null) {
			$options['max'] = $this->max;
			$options['tooLong'] = Yii::$app->getI18n()->format($this->tooLong, [
				'attribute' => $label,
				'max' => $this->max,
			], Yii::$app->language);
		}
		if ($this->length !== null) {
			$options['is'] = $this->length;
			$options['notEqual'] = Yii::$app->getI18n()->format($this->notEqual, [
				'attribute' => $label,
				'length' => $this->length,
			], Yii::$app->language);
		}
		if ($this->skipOnEmpty) {
			$options['skipOnEmpty'] = 1;
		}

		ValidationAsset::register($view);
		return 'yii.validation.string(value, messages, ' . json_encode($options) . ');';
	}
}
