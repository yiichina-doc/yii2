<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\log;

use Yii;
use yii\base\InvalidConfigException;
use yii\mail\MailerInterface;

/**
 * EmailTarget将选定的日志消息发送到指定的电子邮箱.
 *
 * 你可以通过设置[[message]]属性配置发送电子邮件, 
 * 你可以通过它设定目标电子邮件地址, 主题等.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class EmailTarget extends Target
{
	/**
	 * @var array 创建[[\yii\mail\MessageInterface|message]]对象用的配置数组.
	 * 需要注意的是，必须设置"to"选项, 即指定目标电子邮件地址.
	 */
	public $message = [];
	/**
	 * @var MailerInterface|string 邮寄者或对象的邮件收发器对象的应用程序组件ID.
	 * 创建EmailTarget对象后, 如果想改变这个属性, 
	 * 只能通过邮件对象分配.
	 */
	public $mail = 'mail';

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if (empty($this->message['to'])) {
			throw new InvalidConfigException('The "to" option must be set for EmailTarget::message.');
		}
		if (is_string($this->mail)) {
			$this->mail = Yii::$app->getComponent($this->mail);
		}
		if (!$this->mail instanceof MailerInterface) {
			throw new InvalidConfigException("EmailTarget::mailer must be either a mailer object or the application component ID of a mailer object.");
		}
	}

	/**
	 * 发送日志消息到指定的电子邮箱.
	 */
	public function export()
	{
		// moved initialization of subject here because of the following issue
		// https://github.com/yiisoft/yii2/issues/1446
		if (empty($this->message['subject'])) {
			$this->message['subject'] = 'Application Log';
		}
		$messages = array_map([$this, 'formatMessage'], $this->messages);
		$body = wordwrap(implode("\n", $messages), 70);
		$this->composeMessage($body)->send($this->mail);
	}

	/**
	 * 通过给定的主体内容组成电子邮件.
	 * @param string $body 主体内容
	 * @return \yii\mail\MessageInterface $message
	 */
	protected function composeMessage($body)
	{
		$message = $this->mail->compose();
		Yii::configure($message, $this->message);
		$message->setTextBody($body);
		return $message;
	}
}
