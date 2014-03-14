<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\validators;

use yii\web\AssetBundle;

/**
 * 这个资源包提供了[[EmailValidator]]客户端验证时需要的javascript文件。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class PunycodeAsset extends AssetBundle
{
	public $sourcePath = '@yii/assets';
	public $js = [
		'punycode/punycode.js',
	];
}
