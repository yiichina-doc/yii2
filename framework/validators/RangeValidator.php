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
 * RangeValidator validates 该属性值是列表中的值。
 *
 * range可以通过[[range]]属性来设置。
 * 如果[[not]]属性设置为true，validator将确保属性值
 * 不属于指定范围。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class RangeValidator extends Validator
{
	/**
	 * @var array 该属性值应该在其中的有效值列表中。
	 */
	public $range;
	/**
	 * @var boolean 比较是否是严格的（类型和值必须相同）
	 */
	public $strict = false;
	/**
	 * @var boolean 是否反转的验证逻辑。默认为false。如果设置为true，
	 * 该属性值不应该定义在[[range]]列表中。
	 **/
	public $not = false;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if (!is_array($this->range)) {
			throw new InvalidConfigException('The "range" property must be set.');
		}
		if ($this->message === null) {
			$this->message = Yii::t('yii', '{attribute} is invalid.');
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		$valid = !$this->not && in_array($value, $this->range, $this->strict)
			|| $this->not && !in_array($value, $this->range, $this->strict);
		return $valid ? null : [$this->message, []];
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		$range = [];
		foreach ($this->range as $value) {
			$range[] = (string)$value;
		}
		$options = [
			'range' => $range,
			'not' => $this->not,
			'message' => Yii::$app->getI18n()->format($this->message, [
				'attribute' => $object->getAttributeLabel($attribute),
			], Yii::$app->language),
		];
		if ($this->skipOnEmpty) {
			$options['skipOnEmpty'] = 1;
		}

		ValidationAsset::register($view);
		return 'yii.validation.range(value, messages, ' . json_encode($options) . ');';
	}
}
