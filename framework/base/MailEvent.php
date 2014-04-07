<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * ActionEvent 表示用于一个动作事件的事件参数。
 *
 * 通过设置 [[isValid]] 属性，可以控制是否继续执行该操作。
 *
 * @author Mark Jebri <mark.github@yandex.ru>
 * @since 2.0
 */
class MailEvent extends Event
{

	/**
	 * @var \yii\mail\MessageInterface 将要发送的邮件信息
	 */
	public $message;
	/**
	 * @var boolean 是否消息发送成功
	 */
	public $isSuccessful;
	/**
	 * @var boolean 是否继续发送邮件。事件处理函数
	 * [[\yii\mail\BaseMailer::EVENT_BEFORE_SEND]] 可以设置这个属性来决定是否继续发送。
	 */
	public $isValid = true;
}
