<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\mail;

/**
 * MailerInterface 是应该由寄件人类实现的接口。
 *
 * 邮件应主要支持创建和发送邮件 [[MessageInterface|mail messages]]。它也应该
 * 通过视图渲染机制支持消息体组成。例如,
 *
 * ~~~
 * Yii::$app->mail->compose('contact/html', ['contactForm' => $form])
 *     ->setFrom('from@domain.com')
 *     ->setTo($form->email)
 *     ->setSubject($form->subject)
 *     ->send();
 * ~~~
 *
 * @see MessageInterface
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
interface MailerInterface
{
	/**
	 * 创建一个新的消息实例并选择组成它的身体通过视图呈现内容。
	 *
	 * @param string|array $view 是视图用于呈现消息体。这可以是:
	 *
	 * - 一个字符串, 代表视图的名称或路径别名呈现HTML的电子邮件。
	 *  在这种情况下, 将通过应用生成文本主体的 `strip_tags()` HTML的身体。
	 * - 数组与 'html' 和/或 'text' 元素。html的元素指的是视图名称或路径别名呈现html的主体,
	 *   而 'text' 元素是用于渲染文本正文。例如，
	 *   `['html' => 'contact-html', 'text' => 'contact-text']`。
	 * - 空, 这意味着消息实例将返回没有正文内容。
	 *
	 * @param array $params 参数 (name-value pairs) 将在视图中提取并提供文件。
	 * @return MessageInterface 消息实例。
	 */
	public function compose($view = null, array $params = []);

	/**
	 * 发送电子邮件消息。
	 * @param MessageInterface $message 要发送电子邮件消息实例
	 * @return boolean 是否已成功发送的消息
	 */
	public function send($message);

	/**
	 * 一次发送多条消息。
	 *
	 * 该方法可实现批量发送信息
	 *
	 * @param array $messages 应该发送的邮件列表。
	 * @return integer 成功发送的消息数量。
	 */
	public function sendMultiple(array $messages);
}
