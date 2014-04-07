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
 * EmailValidator 验证该属性值是一个有效的email地址。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class EmailValidator extends Validator
{
	/**
	 * @var string 用于验证该属性值的正则表达式。
	 * @see http://www.regular-expressions.info/email.html
	 */
	public $pattern = '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
	/**
	 * @var string 用于验证email地址与名称的正则表达式。
	 * 此属性仅适用于当[[allowName]]为true时。
	 * @see allowName
	 */
	public $fullPattern = '/^[^@]*<[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?>$/';
	/**
	 * @var boolean 是否允许使用名称做为email地址(例如 "John Smith <john.smith@example.com>")。默认设置为false。
	 * @see fullPattern
	 */
	public $allowName = false;
	/**
	 * @var boolean 是否检查邮箱的域名是否存在并且有A记录或者MX记录。
	 * 请注意一个事实，即这种检查可能会失败，即使email地址是有效的， 
	 * 由于临时的 DNS 问题，email将被发送。 默认设置为false。
	 */
	public $checkDNS = false;
	/**
	 * @var boolean 是否验证过程应考虑到 IDN (国际化
	 * 域名)。 默认设置为false 意味着包含IDN的emails验证总是失败.
	 * 请注意为了使用IDN 域名验证，必须安装并启用PHP扩展`intl`，
	 * 否则将会抛出一个异常。
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
			$this->message = Yii::t('yii', '{attribute} is not a valid email address.');
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value)
	{
		// make sure string length is limited to avoid DOS attacks
		if (!is_string($value) || strlen($value) >= 320) {
			$valid = false;
		} elseif (!preg_match('/^(.*<?)(.*)@(.*)(>?)$/', $value, $matches)) {
			$valid = false;
		} else {
			$domain = $matches[3];
			if ($this->enableIDN) {
				$value = $matches[1] . idn_to_ascii($matches[2]) . '@' . idn_to_ascii($domain) . $matches[4];
			}
			$valid = preg_match($this->pattern, $value) || $this->allowName && preg_match($this->fullPattern, $value);
			if ($valid && $this->checkDNS) {
				$valid = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
			}
		}
		return $valid ? null : [$this->message, []];
	}

	/**
	 * @inheritdoc
	 */
	public function clientValidateAttribute($object, $attribute, $view)
	{
		$options = [
			'pattern' => new JsExpression($this->pattern),
			'fullPattern' => new JsExpression($this->fullPattern),
			'allowName' => $this->allowName,
			'message' => Yii::$app->getI18n()->format($this->message, [
				'attribute' => $object->getAttributeLabel($attribute),
			], Yii::$app->language),
			'enableIDN' => (boolean)$this->enableIDN,
		];
		if ($this->skipOnEmpty) {
			$options['skipOnEmpty'] = 1;
		}

		ValidationAsset::register($view);
		if ($this->enableIDN) {
			PunycodeAsset::register($view);
		}
		return 'yii.validation.email(value, messages, ' . Json::encode($options) . ');';
	}
}
