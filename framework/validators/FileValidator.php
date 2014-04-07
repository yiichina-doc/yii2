<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\web\UploadedFile;

/**
 * FileValidator 验证属性是否接收一个有效的上传文件。
 *
 * @property integer $sizeLimit 上传文件大小限制。属性是只读的。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class FileValidator extends Validator
{
	/**
	 * @var array|string 被允许上传的文件扩展名列表。
	 * 可以是一个数组或由文件扩展名组成的字符串
	 * 由空格或逗号分隔 (例如 "gif, jpg")。
	 * 扩展名不区分大小写。默认为null，意味着所有的文件扩展名
	 * 是允许的。
	 * @see wrongType
	 */
	public $types;
	/**
	 * @var integer 需要上载的文件的最小字节数。
	 * 默认为null，意味着没有限制。
	 * @see tooSmall
	 */
	public $minSize;
	/**
	 * @var integer 需要上传的文件的最大字节数。
	 * 默认为null，意味着没有限制.
	 * 请注意，这个大小限制 同时也受INI配置中'upload_max_filesize'
	 * 这个参数 和'MAX_FILE_SIZE'这个隐藏域变量的影响。
	 * @see tooBig
	 */
	public $maxSize;
	/**
	 * @var integer 给定的属性的最大文件数。
	 * 默认设置为1，意味着单个文件上传。通过定义一个较大的数字，
	 * 实现多上传。
	 * @see tooMany
	 */
	public $maxFiles = 1;
	/**
	 * @var string 当文件上传失败时的错误信息。
	 */
	public $message;
	/**
	 * @var string 当没有文件被上传时的错误信息。
	 */
	public $uploadRequired;
	/**
	 * @var string 当上传文件太大时的错误信息。
	 * 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {file}: 上传的文件名
	 * - {limit}: 允许的最大大小 (参见[[getSizeLimit()]])
	 */
	public $tooBig;
	/**
	 * @var string 当上传文件过小时的错误信息。
	 * 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {file}: 上传文件名
	 * - {limit}: [[minSize]]值
	 */
	public $tooSmall;
	/**
	 * @var string 当上传的文件 其后缀不在[[types]]
	 * 这个列表中时，使用这个错误信息。 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {file}: 上传文件名
	 * - {extensions}: 允许扩展名列表。
	 */
	public $wrongType;
	/**
	 * @var string 如果有多个上传文件超过限制时的错误消息。
	 * 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {limit}: [[maxFiles]]值
	 */
	public $tooMany;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = Yii::t('yii', 'File upload failed.');
		}
		if ($this->uploadRequired === null) {
			$this->uploadRequired = Yii::t('yii', 'Please upload a file.');
		}
		if ($this->tooMany === null) {
			$this->tooMany = Yii::t('yii', 'You can upload at most {limit, number} {limit, plural, one{file} other{files}}.');
		}
		if ($this->wrongType === null) {
			$this->wrongType = Yii::t('yii', 'Only files with these extensions are allowed: {extensions}.');
		}
		if ($this->tooBig === null) {
			$this->tooBig = Yii::t('yii', 'The file "{file}" is too big. Its size cannot exceed {limit, number} {limit, plural, one{byte} other{bytes}}.');
		}
		if ($this->tooSmall === null) {
			$this->tooSmall = Yii::t('yii', 'The file "{file}" is too small. Its size cannot be smaller than {limit, number} {limit, plural, one{byte} other{bytes}}.');
		}
		if (!is_array($this->types)) {
			$this->types = preg_split('/[\s,]+/', strtolower($this->types), -1, PREG_SPLIT_NO_EMPTY);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function validateAttribute($object, $attribute)
	{
		if ($this->maxFiles > 1) {
			$files = $object->$attribute;
			if (!is_array($files)) {
				$this->addError($object, $attribute, $this->uploadRequired);
				return;
			}
			foreach ($files as $i => $file) {
				if (!$file instanceof UploadedFile || $file->error == UPLOAD_ERR_NO_FILE) {
					unset($files[$i]);
				}
			}
			$object->$attribute = array_values($files);
			if (empty($files)) {
				$this->addError($object, $attribute, $this->uploadRequired);
			}
			if (count($files) > $this->maxFiles) {
				$this->addError($object, $attribute, $this->tooMany, ['limit' => $this->maxFiles]);
			} else {
				foreach ($files as $file) {
					$result = $this->validateValue($file);
					if (!empty($result)) {
						$this->addError($object, $attribute, $result[0], $result[1]);
					}
				}
			}
		} else {
			$result = $this->validateValue($object->$attribute);
			if (!empty($result)) {
				$this->addError($object, $attribute, $result[0], $result[1]);
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($file)
	{
		if (!$file instanceof UploadedFile || $file->error == UPLOAD_ERR_NO_FILE) {
			return [$this->uploadRequired, []];
		}
		switch ($file->error) {
			case UPLOAD_ERR_OK:
				if ($this->maxSize !== null && $file->size > $this->maxSize) {
					return [$this->tooBig, ['file' => $file->name, 'limit' => $this->getSizeLimit()]];
				} elseif ($this->minSize !== null && $file->size < $this->minSize) {
					return [$this->tooSmall, ['file' => $file->name, 'limit' => $this->minSize]];
				} elseif (!empty($this->types) && !in_array(strtolower(pathinfo($file->name, PATHINFO_EXTENSION)), $this->types, true)) {
					return [$this->wrongType, ['file' => $file->name, 'extensions' => implode(', ', $this->types)]];
				} else {
					return null;
				}
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				return [$this->tooBig, ['file' => $file->name, 'limit' => $this->getSizeLimit()]];
			case UPLOAD_ERR_PARTIAL:
				Yii::warning('File was only partially uploaded: ' . $file->name, __METHOD__);
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				Yii::warning('Missing the temporary folder to store the uploaded file: ' . $file->name, __METHOD__);
				break;
			case UPLOAD_ERR_CANT_WRITE:
				Yii::warning('Failed to write the uploaded file to disk: ' . $file->name, __METHOD__);
				break;
			case UPLOAD_ERR_EXTENSION:
				Yii::warning('File upload was stopped by some PHP extension: ' . $file->name, __METHOD__);
				break;
			default:
				break;
		}
		return [$this->message, []];
	}

	/**
	 * 返回允许上传的最大文件大小。
	 * 由三个因素决定:
	 *
	 * - 'upload_max_filesize' in php.ini
	 * - 'MAX_FILE_SIZE' 隐藏字段
	 * - [[maxSize]]
	 *
	 * @return 上传文件限制的整数大小。
	 */
	public function getSizeLimit()
	{
		$limit = ini_get('upload_max_filesize');
		$limit = $this->sizeToBytes($limit);
		if ($this->maxSize !== null && $limit > 0 && $this->maxSize < $limit) {
			$limit = $this->maxSize;
		}
		if (isset($_POST['MAX_FILE_SIZE']) && $_POST['MAX_FILE_SIZE'] > 0 && $_POST['MAX_FILE_SIZE'] < $limit) {
			$limit = (int)$_POST['MAX_FILE_SIZE'];
		}
		return $limit;
	}

	/**
	 * @inheritdoc
	 */
	public function isEmpty($value, $trim = false)
	{
		return !$value instanceof UploadedFile || $value->error == UPLOAD_ERR_NO_FILE;
	}

	/**
	 * Converts php.ini style size to bytes
	 *
	 * @param string $sizeStr $sizeStr
	 * @return int
	 */
	private function sizeToBytes($sizeStr)
	{
		switch (substr($sizeStr, -1)) {
			case 'M':
			case 'm':
				return (int)$sizeStr * 1048576;
			case 'K':
			case 'k':
				return (int)$sizeStr * 1024;
			case 'G':
			case 'g':
				return (int)$sizeStr * 1073741824;
			default:
				return (int)$sizeStr;
		}
	}
}
