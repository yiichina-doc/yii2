<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

/**
 * Response 表示响应 [[Application]] 的 [[Request]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Response extends Component
{
	/**
	 * @var integer 退出状态. 退出状态应在0到254之间.
	 * 状态0成功意味着程序终止.
	 */
	public $exitStatus = 0;

	/**
	 * 将响应发送给客户机.
	 */
	public function send()
	{
	}

	/**
	 * 删除所有现有的输出缓冲区.
	 */
	public function clearOutputBuffers()
	{
		// the following manual level counting is to deal with zlib.output_compression set to On
		for ($level = ob_get_level(); $level > 0; --$level) {
			if (!@ob_end_clean()) {
				ob_clean();
			}
		}
	}
}
