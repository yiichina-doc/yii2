<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\mail;

use yii\base\Object;
use Yii;

/**
 * BaseMessage 作为基类实现 [[MessageInterface]] 提供的 [[send()]] 方法。
 *
 * 默认情况下, [[send()]] 将使用 "mail" 应用程序组件发送当前的消息。
 * "mail" 应用程序组件应该是一个邮件程序实例实施 [[MailerInterface]。
 *
 * @see BaseMailer
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
abstract class BaseMessage extends Object implements MessageInterface
{
	/**
	 * @inheritdoc
	 */
	public function send(MailerInterface $mailer = null)
	{
		if ($mailer === null) {
			$mailer = Yii::$app->getMail();
		}
		return $mailer->send($this);
	}

	/**
	 * PHP魔术方法，返回此对象的字符串表示形式。
	 * @return string 该对象的字符串表示。
	 */
	public function __toString()
	{
		// __toString 不抛出异常
		// 使用 trigger_error 绕过这个限制
		try {
			return $this->toString();
		} catch (\Exception $e) {
			trigger_error($e->getMessage() . "\n\n" . $e->getTraceAsString());
			return '';
		}
	}
}
