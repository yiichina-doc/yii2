<?php
/**
 * Image validator class file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use Yii;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

/**
 * ImageValidator 验证是否一个属性接收一个有效的image。
 *
 * @author Taras Gudz <gudz.taras@gmail.com>
 * @since 2.0
 */
class ImageValidator extends FileValidator
{
	/**
	 * @var string 当上传的文件不是一个image时的错误信息。
	 * 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {file}: 上传文件名
	 */
	public $notImage;
	/**
	 * @var integer 以像素为单位的最小宽度。
	 * 默认为 null, 意味着无限制。
	 * @see underWidth
	 */
	public $minWidth;
	/**
	 * @var integer 以像素为单位的最大宽度。
	 * 默认为 null, 意味着无限制.
	 * @see overWidth
	 */
	public $maxWidth;
	/**
	 * @var integer 以像素为单位的最小高度。
	 * 默认为 null, 意味着无限制.
	 * @see underHeight
	 */
	public $minHeight;
	/**
	 * @var integer 以像素为单位的最大高度。
	 * 默认为 null, 意味着无限制。
	 * @see overWidth
	 */
	public $maxHeight;
	/**
	 * @var array|string 被允许上载文件的mime类型的列表。
	 * 可以是一个数组或包含由空格或逗号分隔的mime文件
	 * 类型的字符串 (例如 "image/jpeg, image/png")。
	 * Mime类型名称是区分大小写。 默认为 null, 意味着所有的
	 * 类型是允许的。
	 * @see wrongMimeType
	 */
	public $mimeTypes;
	/**
	 * @var string 当image的最小宽度在[[minWidth]]以下时的错误消息。
	 * 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {file}: 上传文件名
	 * - {limit}: [[minWidth]]值
	 */
	public $underWidth;
	/**
	 * @var string 当image的最大宽度在[[maxWidth]]以上时的错误消息。
	 * 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {file}: 上传文件名
	 * - {limit}: [[maxWidth]]值
	 */
	public $overWidth;
	/**
	 * @var string 当image的最小高度在[[minHeight]]以下时的错误消息。
	 * 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {file}: 上传文件名
	 * - {limit}:[[minHeight]]值
	 */
	public $underHeight;
	/**
	 * @var string 当image的最大高度在[[maxHeight]]以上时的错误消息。
	 * 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {file}: 上传文件名
	 * - {limit}:[[maxHeight]]值
	 */
	public $overHeight;
	/**
	 * @var string [[mimeTypes]]中列出的类型中
	 * 没有mime类的错误消息。
	 * 您可以使用以下tokens消息:
	 *
	 * - {attribute}: 属性名
	 * - {file}: 上传文件名
	 * - {mimeTypes}: [[mimeTypes]]值
	 */
	public $wrongMimeType;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
		
		if ($this->notImage === null) {
			$this->notImage = Yii::t('yii', 'The file "{file}" is not an image.');
		}
		if ($this->underWidth === null) {
			$this->underWidth = Yii::t('yii', 'The image "{file}" is too small. The width cannot be smaller than {limit, number} {limit, plural, one{pixel} other{pixels}}.');
		}
		if ($this->underHeight === null) {
			$this->underHeight = Yii::t('yii', 'The image "{file}" is too small. The height cannot be smaller than {limit, number} {limit, plural, one{pixel} other{pixels}}.');
		}
		if ($this->overWidth === null) {
			$this->overWidth = Yii::t('yii', 'The image "{file}" is too large. The width cannot be larger than {limit, number} {limit, plural, one{pixel} other{pixels}}.');
		}
		if ($this->overHeight === null) {
			$this->overHeight = Yii::t('yii', 'The image "{file}" is too large. The height cannot be larger than {limit, number} {limit, plural, one{pixel} other{pixels}}.');
		}
		if ($this->wrongMimeType === null) {
			$this->wrongMimeType = Yii::t('yii', 'Only files with these mimeTypes are allowed: {mimeTypes}.');
		}
		if (!is_array($this->mimeTypes)) {
			$this->mimeTypes = preg_split('/[\s,]+/', strtolower($this->mimeTypes), -1, PREG_SPLIT_NO_EMPTY);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($file)
	{
		$result = parent::validateValue($file);
		return empty($result) ? $this->validateImage($file) : $result;
	}
	
	/**
	 * 验证图像文件。
	 * @param UploadedFile $image 上传的文件通过了一套规则的检查
	 * @return array|null 参数被插入到错误消息中。
	 * 如果该数据是有效的则应返回Null。
	 */
	protected function validateImage($image)
	{
		if (!empty($this->mimeTypes) && !in_array(FileHelper::getMimeType($image->tempName), $this->mimeTypes, true)) {
			return [$this->wrongMimeType, ['file' => $image->name, 'mimeTypes' => implode(', ', $this->mimeTypes)]];
		}
		
		if (false === ($imageInfo = getimagesize($image->tempName))) {
			return [$this->notImage, ['file' => $image->name]];
		}
		
		list($width, $height, $type) = $imageInfo;
		
		if ($width == 0 || $height == 0) {
			return [$this->notImage, ['file' => $image->name]];
		}
		
		if ($this->minWidth !== null && $width < $this->minWidth) {
			return [$this->underWidth, ['file' => $image->name, 'limit' => $this->minWidth]];
		}
		
		if ($this->minHeight !== null && $height < $this->minHeight) {
			return [$this->underHeight, ['file' => $image->name, 'limit' => $this->minHeight]];
		}
		
		if ($this->maxWidth !== null && $width > $this->maxWidth) {
			return [$this->overWidth, ['file' => $image->name, 'limit' => $this->maxWidth]];
		}
		
		if ($this->maxHeight !== null && $height > $this->maxHeight) {
			return [$this->overHeight, ['file' => $image->name, 'limit' => $this->maxHeight]];
		}
		return null;
	}
}
