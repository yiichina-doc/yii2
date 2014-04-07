<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\JsExpression;
use yii\helpers\Json;

/**
 * UrlValidator 验证该属性值是一个有效的http 或 https URL。
 *
 * 需要注意的是这个验证只检查URL方案和host part是否是正确的。
 * 它不检查URL的其余部分。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class UrlValidator extends Validator
{
	/**
	 * @var string 用于验证该属性值的正则表达式。
	 * 此模式可以包含一个 `{schemes}` token 被表示
	 * [[validSchemes]]的正则表达试替换。
	 */
	public $pattern = '/^{schemes}:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)+)/i';
	/**
	 * @var array URI方案列表被认为是有效。 默认, http and https
	 * 被认为是有效的。
	 **/
	public $validSchemes = ['http', 'https'];
	/**
	 * @var string 默认的URI方案。如果输入不包含该计划的一部分, 默认方案
	 * 将被附加到它（从而改变了输入）。默认为null，意味着一个URL
	 * 必须包含方案的一部分。
	 **/
	public $defaultScheme;
	/**
	 * @var boolean 验证过程是否应考虑到IDN （国际化
	 * 域名）。 默认为false意味着URLs中包含IDN的验证总是
	 * 失败。 请注意，为了使用国际化域名验证，你必须安装并启用`intl` PHP
	 * 扩展，否则会抛出一个异常。
	 */
	public $enableIDN = false;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->enableIDN && !function_exists('idn_to_ascii')) {
			throw new InvalidConfigException('In order to use IDN validation intl extension must be installed and enabled.');
		}
		if ($this->message === null) {
			$this->message = Yii::t('yii', '{attribute} is not a valid URL.');
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
		} elseif ($this->defaultScheme !== null && strpos($value, '://') === false) {
			$object->$attribute = $this->defaultScheme . '://' . $value;
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		// make sure the length is limited to avoid DOS attacks
		if (is_string($value) && strlen($value) < 2000) {
			if ($this->defaultScheme !== null && strpos($value, '://') === false) {
				$value = $this->defaultScheme . '://' . $value;
			}

			if (strpos($this->pattern, '{schemes}') !== false) {
				$pattern = str_replace('{schemes}', '(' . implode('|', $this->validSchemes) . ')', $this->pattern);
			} else {
				$pattern = $this->pattern;
			}

			if ($this->enableIDN) {
				$value = preg_replace_callback('/:\/\/([^\/]+)/', function ($matches) {
					return '://' . idn_to_ascii($matches[1]);
				}, $value);
			}

			if (preg_match($pattern, $value)) {
				return null;
			}
		}
		return [$this->message, []];
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		if (strpos($this->pattern, '{schemes}') !== false) {
			$pattern = str_replace('{schemes}', '(' . implode('|', $this->validSchemes) . ')', $this->pattern);
		} else {
			$pattern = $this->pattern;
		}

		$options = [
			'pattern' => new JsExpression($pattern),
			'message' => Yii::$app->getI18n()->format($this->message, [
				'attribute' => $object->getAttributeLabel($attribute),
			], Yii::$app->language),
			'enableIDN' => (boolean)$this->enableIDN,
		];
		if ($this->skipOnEmpty) {
			$options['skipOnEmpty'] = 1;
		}
		if ($this->defaultScheme !== null) {
			$options['defaultScheme'] = $this->defaultScheme;
		}

		ValidationAsset::register($view);
		if ($this->enableIDN) {
			PunycodeAsset::register($view);
		}
		return 'yii.validation.url(value, messages, ' . Json::encode($options) . ');';
	}
}
