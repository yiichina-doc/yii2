<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\mail;

/**
 * MessageInterface是邮件消息的接口。
 *
 * 一个消息代表电子邮件的设置和内容, 如发件人, 收件人,
 * 主题,正文等。
 *
 * 消息被发送到 [[\yii\mail\MailerInterface|mailer]], 例如下面的,
 *
 * ~~~
 * Yii::$app->mail->compose()
 *     ->setFrom('from@domain.com')
 *     ->setTo($form->email)
 *     ->setSubject($form->subject)
 *     ->setTextBody('Plain text content')
 *     ->setHtmlBody('<b>HTML content</b>')
 *     ->send();
 * ~~~
 *
 * @see MailerInterface
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 2.0
 */
interface MessageInterface
{
	/**
	 * 返回此消息的字符集。
	 * @return string 此消息的字符集。
	 */
	public function getCharset();

	/**
	 * 设置此消息的字符集。
	 * @param string $charset 字符集名称。
	 * @return static 自我参考。
	 */
	public function setCharset($charset);

	/**
	 * 返回消息发送者。
	 * @return string 发送方
	 */
	public function getFrom();

	/**
	 * 设置消息发送者。
	 * @param string|array $from 发送者电子邮件地址。
	 * 如果同时发送给多个人,可以用数组存储发送者地址
	 * 你也可以指定发件人名称除了使用格式的电子邮件地址:
	 * `[email => name]`.
	 * @return static 自我参考。
	 */
	public function setFrom($from);

	/**
	 * 返回消息的接收者(s)。
	 * @return array 消息的接收者
	 */
	public function getTo();

	/**
	 * 设置消息接收者(s)。
	 * @param string|array $to 接收方的电子邮件地址。
	 * 如果多个接收者应该收到此消息,你可以通过一个数组的地址。
	 * 你也可以指定接收方名称除了使用格式的电子邮件地址:
	 * `[email => name]`.
	 * @return static 自我参考。
	 */
	public function setTo($to);

	/**
	 * 返回此消息的答复地址。
	 * @return string 此消息的答复地址。
	 */
	public function getReplyTo();

	/**
	 * 此消息的答复地址集。
	 * @param string|array $replyTo 回复地址。
	 * 如果这个消息应该回答多个人,你可以通过一个数组的地址。
	 * 您也可以在除了使用格式电子邮件地址指定答复人名称：
	 * `[email => name]`.
	 * @return static 自我参考。
	 */
	public function setReplyTo($replyTo);

	/**
	 * 返回副本 (additional copy receiver) 这个消息的地址。
	 * @return array 此消息的副本 (additional copy receiver) 地址。
	 */
	public function getCc();

	/**
	 * 设置此消息的副本 (additional copy receiver) 地址。
	 * @param string|array $cc 复制接收方的电子邮件地址。
	 * 如果多个接收者应该收到此消息,你可以通过一个数组的地址。
	 * 你也可以指定接收方名称除了使用格式的电子邮件地址:
	 * `[email => name]`.
	 * @return static 自我参考。
	 */
	public function setCc($cc);

	/**
	 * 返回隐藏副本接收 (hidden copy receiver) 这个消息的地址。
	 * @return array 隐藏副本接收 (hidden copy receiver) 地址的信息。
	 */
	public function getBcc();

	/**
	 * 隐藏副本接收 (hidden copy receiver) 地址的信息。
	 * @param string|array $bcc 隐藏复制接收方的电子邮件地址。
	 * 如果多个接收者应该收到此消息,你可以通过一个数组的地址。
	 * 你也可以指定接收方名称除了使用格式的电子邮件地址:
	 * `[email => name]`.
	 * @return static 自我参考。
	 */
	public function setBcc($bcc);

	/**
	 * 返回消息的主题。
	 * @return string 消息主题
	 */
	public function getSubject();

	/**
	 * 设置消息主题
	 * @param string $subject 消息主题
	 * @return static 自我参考。
	 */
	public function setSubject($subject);

	/**
	 * 设置消息纯文本内容。
	 * @param string $text 消息纯文本内容。
	 * @return static 自我参考。
	 */
	public function setTextBody($text);

	/**
	 * 设置邮件的HTML内容。
	 * @param string $html 消息的HTML内容。
	 * @return static 自我参考。
	 */
	public function setHtmlBody($html);

	/**
	 * 发送电子邮件附件
	 * @param string $fileName 发送附件的名字。
	 * @param array $options 选择嵌入文件。有效的选项是:
	 *
	 * - 文件名: 名字, 应该使用哪一个附加文件。
	 * - 内容类型: 附加文件的MIME类型。
	 *
	 * @return static 自我参考。
	 */
	public function attach($fileName, array $options = []);

	/**
	 * 附加内容中指定的电子邮件消息。
	 * @param string $content 附件文件内容。
	 * @param array $options 选择嵌入文件。有效的选项是:
	 *
	 * - 文件名: 名字, 应该使用哪一个附加文件。
	 * - 内容类型: 附加文件的MIME类型。
	 *
	 * @return static 自我参考。
	 */
	public function attachContent($content, array $options = []);

	/**
	 * 附上一个文件并返回它的 CID 来源。
	 * 这种方法应该使用在消息中嵌入图像或其他数据。
	 * @param string $fileName 文件名。
	 * @param array $options 选择嵌入文件。有效的选项是:
	 *
	 * - 文件名: 名字, 应该使用哪一个附加文件。
	 * - 内容类型: 附加文件的MIME类型。
	 *
	 * @return string 附件 CID。
	 */
	public function embed($fileName, array $options = []);

	/**
	 * 附加一个内容文件并返回它的 CID 来源。
	 * 这种方法应该使用在消息中嵌入图像或其他数据。
	 * @param string $content  附件文件内容。
	 * @param array $options 选择嵌入文件。有效的选项是:
	 *
	 * - 文件名: 名字, 应该使用哪一个附加文件。
	 * - 内容类型: 附加文件的MIME类型。
	 *
	 * @return string 附件 CID。
	 */
	public function embedContent($content, array $options = []);

	/**
	 * 发送此电子邮件消息。
	 * @param MailerInterface $mailer 应该被用来发送该消息的邮件。
	 * 如果为空, "mail" 应用程序组件将被代替使用。
	 * @return boolean 这个消息是否发送成功。
	 */
	public function send(MailerInterface $mailer = null);

	/**
	 * 返回此消息的字符串表示。
	 * @return string 此消息的字符串表示。
	 */
	public function toString();
}
