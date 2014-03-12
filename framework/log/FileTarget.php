<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\log;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

/**
 * FileTarget 在一个文件中记录日志消息.
 *
 * 日志文件是通过指定 [[logFile]]. 如果日志文件的大小超过
 * [[maxFileSize]] (千字节), 将执行轮转操作, 
 * 使用后缀名'.1'重命名当前日志文件. 所有现有的日志文件
 * 是由一处向后移动, i.e., '.2' to '.3', '.1' to '.2', 等等.
 * [[maxLogFiles]] 这个属性 指定了保留的文件数.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class FileTarget extends Target
{
	/**
	 * @var string 日志文件的路径或路径别名. 如果未设置, 默认使用 "@runtime/logs/app.log" 文件名.
	 * 如果不存在，将自动创建一个包含日志文件的目录.
	 */
	public $logFile;
	/**
	 * @var integer 最大日志文件大小, 以千字节. 默认设置为10240, 即 10MB.
	 */
	public $maxFileSize = 10240; // in KB
	/**
	 * @var integer 轮转日志文件数.默认设置为5.
	 */
	public $maxLogFiles = 5;
	/**
	 * @var integer 对新创建的日志文件设置权限.
	 * 这个值将被PHP的chmod()函数使用. 没有umask被使用.
	 * 若未设置, 此权限将由当前环境而定.
	 */
	public $fileMode;
	/**
	 * @var integer 对新创建的目录设置权限.
	 * 这个值将被PHP的chmod()函数使用. 没有umask被使用.
	 * 默认设置为0775, 这意味着该目录对所有者或组是可读可写的,
	 * 但对其它用户只读.
	 */
	public $dirMode = 0775;


	/**
	 * 初始化路径.
	 * 管理员创建路径之后，此方法被调用.
	 */
	public function init()
	{
		parent::init();
		if ($this->logFile === null) {
			$this->logFile = Yii::$app->getRuntimePath() . '/logs/app.log';
		} else {
			$this->logFile = Yii::getAlias($this->logFile);
		}
		$logPath = dirname($this->logFile);
		if (!is_dir($logPath)) {
			FileHelper::createDirectory($logPath, $this->dirMode, true);
		}
		if ($this->maxLogFiles < 1) {
			$this->maxLogFiles = 1;
		}
		if ($this->maxFileSize < 1) {
			$this->maxFileSize = 1;
		}
	}

	/**
	 * 写日志信息到文件.
	 * @throws InvalidConfigException 如果无法打开并写入日志文件
	 */
	public function export()
	{
		$text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
		if (($fp = @fopen($this->logFile, 'a')) === false) {
			throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
		}
		@flock($fp, LOCK_EX);
		if (@filesize($this->logFile) > $this->maxFileSize * 1024) {
			$this->rotateFiles();
			@flock($fp, LOCK_UN);
			@fclose($fp);
			@file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
		} else {
			@fwrite($fp, $text);
			@flock($fp, LOCK_UN);
			@fclose($fp);
		}
		if ($this->fileMode !== null) {
			@chmod($this->logFile, $this->fileMode);
		}
	}

	/**
	 * 轮转日志文件.
	 */
	protected function rotateFiles()
	{
		$file = $this->logFile;
		for ($i = $this->maxLogFiles; $i > 0; --$i) {
			$rotateFile = $file . '.' . $i;
			if (is_file($rotateFile)) {
				// suppress errors because it's possible multiple processes enter into this section
				if ($i === $this->maxLogFiles) {
					@unlink($rotateFile);
				} else {
					@rename($rotateFile, $file . '.' . ($i + 1));
				}
			}
		}
		if (is_file($file)) {
			@rename($file, $file . '.1'); // suppress errors because it's possible multiple processes enter into this section
		}
	}
}
