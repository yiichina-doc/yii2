<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\log;

use Yii;
use yii\db\Connection;
use yii\base\InvalidConfigException;

/**
 * DbTarget在一个数据库的表中存储日志信息.
 *
 * 默认情况下, DbTarget 存储在一个名为 'tbl_log'的数据库表中的日志消息中. 此表
 * 必须预先创建. 表名可以通过设置 [[logTable]]更改.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class DbTarget extends Target
{
	/**
	 * @var Connection|string 数据库连接对象或数据库连接的应用程序组件的ID.
	 * 创建DbTarget对象之后, 如果你想改变这个属性, 你应该使用一个数据库链接对象
	 * 来分配它.
	 */
	public $db = 'db';
	/**
	 * @var string 数据库表的名称来存储缓存内容.
	 * 该表应按照如下方式预先创建:
	 *
	 * ~~~
	 * CREATE TABLE tbl_log (
	 *	   id       BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY,
	 *	   level    INTEGER,
	 *	   category VARCHAR(255),
	 *	   log_time INTEGER,
	 *	   message  TEXT,
	 *     INDEX idx_log_level (level),
	 *     INDEX idx_log_category (category)
	 * )
	 * ~~~
	 *
	 * 请注意，'id' 列必须创建为auto-incremental.
	 * 以上的 SQL 使用的是 MySQL 语法. 如果你使用的是其他数据库管理系统, 需要
	 * 相应地作调整. 例如, 在 PostgreSQL中, id 应该是 serial 类型的主键.
	 *
	 * 上述所声明的不需要索引. 它们主要用于改善有关
	 * 消息级别和类别的一些查询的性能. 根据您的实际需求, 可能
	 * 需要创建额外的索引 (e.g. index on `log_time`).
	 */
	public $logTable = '{{%log}}';

	/**
	 * 初始化DbTarget组件.
	 * 初始化 [[db]] 组件，确保它是一个有效的数据库连接.
	 * @throws InvalidConfigException 如果 [[db]] 是无效的.
	 */
	public function init()
	{
		parent::init();
		if (is_string($this->db)) {
			$this->db = Yii::$app->getComponent($this->db);
		}
		if (!$this->db instanceof Connection) {
			throw new InvalidConfigException("DbTarget::db must be either a DB connection instance or the application component ID of a DB connection.");
		}
	}

	/**
	 * 将日志信息存储到数据库.
	 */
	public function export()
	{
		$tableName = $this->db->quoteTableName($this->logTable);
		$sql = "INSERT INTO $tableName ([[level]], [[category]], [[log_time]], [[message]])
				VALUES (:level, :category, :log_time, :message)";
		$command = $this->db->createCommand($sql);
		foreach ($this->messages as $message) {
			$command->bindValues([
				':level' => $message[1],
				':category' => $message[2],
				':log_time' => $message[3],
				':message' => $message[0],
			])->execute();
		}
	}
}
