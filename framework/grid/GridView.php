<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @translate francis.tm <francis.tm@me.com>
 */

namespace yii\grid;

use Yii;
use Closure;
use yii\base\Formatter;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\BaseListView;

/**
 * GridView 挂件是用来显示数据的一个表格。
 *
 * 他提供了类似排序、分页还有过滤等功能。
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class GridView extends BaseListView
{
	const FILTER_POS_HEADER = 'header';
	const FILTER_POS_FOOTER = 'footer';
	const FILTER_POS_BODY = 'body';

	/**
   * @var string 当没有明确的配置数据类名称时，默认类名。
   * 默认为 `yii\grid\DataColumn`。
	 */
	public $dataColumnClass;
	/**
   * @var string 表头的标题
	 * @see captionOptions
	 */
	public $caption;
	/**
   * @var array 表头的HTML标签属性
	 * @see caption
	 */
	public $captionOptions = [];
	/**
	 * @var array 表内容Table标签的HTML属性
	 */
	public $tableOptions = ['class' => 'table table-striped table-bordered'];
	/**
   * @var array 表容器的HTML标签属性
   * 标签元素定义了容器标签的名称，默认为"div"。
	 */
	public $options = ['class' => 'grid-view'];
	/**
   * @var array 表头这一行的HTML标签属性
	 */
	public $headerRowOptions = [];
	/**
   * @var array 表尾这一行的HTML标签属性
	 */
	public $footerRowOptions = [];
	/**
   * @var array|Closure 表主体中的行所用的HTML标签属性。这个可以是一个数组，定义了所有数据行的
   * 公共HTML标签属性；也可以是一个匿名函数，来返回一个包含HTML标签属性的数组。匿名函数会在每一个
   * 由 [[dataProvider]]] 提供的数据模型的地方被调用。他必须有一下特征：
	 *
	 * ```php
	 * function ($model, $key, $index, $grid)
	 * ```
	 *
	 * - `$model`: 当前正在渲染的模型
	 * - `$key`: 当前模型所需渲染的键值
	 * - `$index`: 零起始的索引，当前模型在由 [[dataProvider]] 所返回的数组中的索引。
	 * - `$grid`: GridView 对象
	 */
	public $rowOptions = [];
	/**
   * @var Closure 一个匿名函数，会在渲染每一个数据模型前调用。
   * 它的特征和 [[rowOptions]] 相似。函数的返回结果将会直接被渲染。
	 */
	public $beforeRow;
	/**
   * @var Closure 一个匿名函数，会在渲染每一个数据模型后调用。
   * 它的特征和 [[rowOptions]] 相似。函数的返回结果将会直接被渲染。
	 */
	public $afterRow;
	/**
   * @var boolean 是否显示表头。
	 */
	public $showHeader = true;
	/**
   * @var boolean 是否显示表尾。
	 */
	public $showFooter = false;
	/**
   * @var boolean 当 [[dataProvider]] 中无数据时，是否显示表格。
	 */
	public $showOnEmpty = true;
	/**
   * @var array|Formatter 用于格式化数据模型的属性，以显示成文字
   * 可以是一个 [[Formatter]] 的实例化对象，也可以是一个 [[Formatter]] 的初始化配置数组。
   * 如果此属性没有被设置，“Formatter”组件将被作为默认是用。
	 */
	public $formatter;
	/**
   * @var array 表格列的配置数组。每一个元素表示一个特定的表哥列。如下例：
	 *
	 * ```php
	 * [
	 *     ['class' => SerialColumn::className()],
	 *     [
	 *         'class' => DataColumn::className(),
	 *         'attribute' => 'name',
	 *         'format' => 'text',
	 *         'label' => 'Name',
	 *     ],
	 *     ['class' => CheckboxColumn::className()],
	 * ]
	 * ```
	 *
   * 如果一个列的类被设置为 [[DataColumn]] ,则“class”处的值可以省略。
	 *
   * 作为一种快捷格式，一个简单的字符串也可以作为数据列的配置，只需要包含“属性”，“格式”和/或“标签”。
   * 比如：`"attribute:format:label"` 。
   * 如上例，"name"这一列可以定义为 `"name:text:Name"` 。
   * "format"和"label"都是课选参数。他们会用默认参数所替代。
	 */
	public $columns = [];
	public $emptyCell = '&nbsp;';
	/**
   * @var \yii\base\Model 模型保存了用户输入的筛选参数。当这个属性被设置了以后，表格会启用基于列的筛选功能。
   * 每一个数据列将默认显示一个文本框在顶部，用户可以在此输入内容来筛选数据。
	 *
   * 注意，要显示文本框来筛选数据，这个列必须有 [[DataColumn::attribute]] 参数，或者 [[DataColumn::filter]] 的
   * 属性，来输出一个文本框的HTML标签。
	 *
   * 当这个属性没有设置（为nil）时，数据筛选功能将会被关闭。
	 */
	public $filterModel;
	/**
   * @var string|array 用于返回筛选结果的URL。 [[Html::url()]] 方法将会被调用来标准化URL。如果没有设置，
   * 当前的控制器动作就会被用作请求地址。
   * 当用户改变任何筛选器内容的时候，当前文本框内的内容就会被以GET参数的形势，加入到这个URL中。
	 */
	public $filterUrl;
	public $filterSelector;
	/**
   * @var string 是否要显示筛选器。有效参数如下：
	 *
	 * - [[FILTER_POS_HEADER]]: 筛选器将会显示在每一列的顶端。
	 * - [[FILTER_POS_BODY]]: 筛选器将会显示在每列头部的下方。
	 * - [[FILTER_POS_FOOTER]]: 筛选器将显示在每一列尾部的单元格中。
	 */
	public $filterPosition = self::FILTER_POS_BODY;
	/**
	 * @var array 筛选器那一行的HTML标签属性。
	 */
	public $filterRowOptions = ['class' => 'filters'];

	/**
   * 初始化数据表。
   * 此方法将初始化必要的属性，实例化 [[columns]] 对象。
	 */
	public function init()
	{
		parent::init();
		if ($this->formatter == null) {
			$this->formatter = Yii::$app->getFormatter();
		} elseif (is_array($this->formatter)) {
			$this->formatter = Yii::createObject($this->formatter);
		}
		if (!$this->formatter instanceof Formatter) {
			throw new InvalidConfigException('The "formatter" property must be either a Format object or a configuration array.');
		}
		if (!isset($this->options['id'])) {
			$this->options['id'] = $this->getId();
		}
		if (!isset($this->filterRowOptions['id'])) {
			$this->filterRowOptions['id'] = $this->options['id'] . '-filters';
		}

		$this->initColumns();
	}

	/**
   * 运行此挂件。
	 */
	public function run()
	{
		$id = $this->options['id'];
		$options = Json::encode($this->getClientOptions());
		$view = $this->getView();
		GridViewAsset::register($view);
		$view->registerJs("jQuery('#$id').yiiGridView($options);");
		parent::run();
	}


	/**
   * 返回此数据表的JS挂件选项
	 * @return array JS选项
	 */
	protected function getClientOptions()
	{
		$filterUrl = isset($this->filterUrl) ? $this->filterUrl : [Yii::$app->controller->action->id];
		$id = $this->filterRowOptions['id'];
		$filterSelector = "#$id input, #$id select";
		if (isset($this->filterSelector)) {
			$filterSelector .= ', ' . $this->filterSelector;
		}

		return [
			'filterUrl' => Html::url($filterUrl),
			'filterSelector' => $filterSelector,
		];
	}

	/**
   * 根据提供的数据模型渲染数据表。
	 */
	public function renderItems()
	{
		$content = array_filter([
			$this->renderCaption(),
			$this->renderColumnGroup(),
			$this->showHeader ? $this->renderTableHeader() : false,
			$this->showFooter ? $this->renderTableFooter() : false,
			$this->renderTableBody(),
		]);
		return Html::tag('table', implode("\n", $content), $this->tableOptions);
	}

	public function renderCaption()
	{
		if (!empty($this->caption)) {
			return Html::tag('caption', $this->caption, $this->captionOptions);
		} else {
			return false;
		}
	}

	public function renderColumnGroup()
	{
		$requireColumnGroup = false;
		foreach ($this->columns as $column) {
			/** @var Column $column */
			if (!empty($column->options)) {
				$requireColumnGroup = true;
				break;
			}
		}
		if ($requireColumnGroup) {
			$cols = [];
			foreach ($this->columns as $column) {
				$cols[] = Html::tag('col', '', $column->options);
			}
			return Html::tag('colgroup', implode("\n", $cols));
		} else {
			return false;
		}
	}

	/**
   * 渲染数据表表头。
   * @return string 渲染结果
	 */
	public function renderTableHeader()
	{
		$cells = [];
		foreach ($this->columns as $column) {
			/** @var Column $column */
			$cells[] = $column->renderHeaderCell();
		}
		$content = Html::tag('tr', implode('', $cells), $this->headerRowOptions);
		if ($this->filterPosition == self::FILTER_POS_HEADER) {
			$content = $this->renderFilters() . $content;
		} elseif ($this->filterPosition == self::FILTER_POS_BODY) {
			$content .= $this->renderFilters();
		}

		return "<thead>\n" .  $content . "\n</thead>";
	}

	/**
   * 渲染数据表表尾
	 * @return string 渲染结果
	 */
	public function renderTableFooter()
	{
		$cells = [];
		foreach ($this->columns as $column) {
			/** @var Column $column */
			$cells[] = $column->renderFooterCell();
		}
		$content = Html::tag('tr', implode('', $cells), $this->footerRowOptions);
		if ($this->filterPosition == self::FILTER_POS_FOOTER) {
			$content .= $this->renderFilters();
		}
		return "<tfoot>\n" . $content . "\n</tfoot>";
	}

	/**
   * 渲染筛选器
	 */
	public function renderFilters()
	{
		if ($this->filterModel !== null) {
			$cells = [];
			foreach ($this->columns as $column) {
				/** @var Column $column */
				$cells[] = $column->renderFilterCell();
			}
			return Html::tag('tr', implode('', $cells), $this->filterRowOptions);
		} else {
			return '';
		}
	}

	/**
   * 渲染表体
	 * @return string 渲染结果
	 */
	public function renderTableBody()
	{
		$models = array_values($this->dataProvider->getModels());
		$keys = $this->dataProvider->getKeys();
		$rows = [];
		foreach ($models as $index => $model) {
			$key = $keys[$index];
			if ($this->beforeRow !== null) {
				$row = call_user_func($this->beforeRow, $model, $key, $index, $this);
				if (!empty($row)) {
					$rows[] = $row;
				}
			}

			$rows[] = $this->renderTableRow($model, $key, $index);

			if ($this->afterRow !== null) {
				$row = call_user_func($this->afterRow, $model, $key, $index, $this);
				if (!empty($row)) {
					$rows[] = $row;
				}
			}
		}

		if (empty($rows)) {
			$colspan = count($this->columns);
			return "<tbody>\n<tr><td colspan=\"$colspan\">" . $this->renderEmpty() . "</td></tr>\n</tbody>";
		} else {
			return "<tbody>\n" . implode("\n", $rows) . "\n</tbody>";
		}
	}

	/**
   * 根据给定数据模型的键值，渲染数据行
   * @param mixed $model 给定的数据模型
   * @param mixed $key 模型中要渲染的键值
   * @param integer $index 由 [[dataProvider]] 所提供数组中，零起始的索引
	 * @return string 渲染结果
	 */
	public function renderTableRow($model, $key, $index)
	{
		$cells = [];
		/** @var Column $column */
		foreach ($this->columns as $column) {
			$cells[] = $column->renderDataCell($model, $key, $index);
		}
		if ($this->rowOptions instanceof Closure) {
			$options = call_user_func($this->rowOptions, $model, $key, $index, $this);
		} else {
			$options = $this->rowOptions;
		}
		$options['data-key'] = is_array($key) ? json_encode($key) : (string)$key;
		return Html::tag('tr', implode('', $cells), $options);
	}

	/**
   * 创建多个列对象，并初始化它们。
	 */
	protected function initColumns()
	{
		if (empty($this->columns)) {
			$this->guessColumns();
		}
		foreach ($this->columns as $i => $column) {
			if (is_string($column)) {
				$column = $this->createDataColumn($column);
			} else {
				$column = Yii::createObject(array_merge([
					'class' => $this->dataColumnClass ?: DataColumn::className(),
					'grid' => $this,
				], $column));
			}
			if (!$column->visible) {
				unset($this->columns[$i]);
				continue;
			}
			$this->columns[$i] = $column;
		}
	}

	/**
	 * 基于 "attribute:format:label" 创建一个 [[DataColumn]] 对象。
	 * @param string $text 该列的详细描述字符串
	 * @return DataColumn 列本身的实例化对象
	 * @throws InvalidConfigException 当列描述字段无效时抛出
	 */
	protected function createDataColumn($text)
	{
		if (!preg_match('/^([\w\.]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
			throw new InvalidConfigException('The column must be specified in the format of "attribute", "attribute:format" or "attribute:format:label"');
		}
		return Yii::createObject([
			'class' => $this->dataColumnClass ?: DataColumn::className(),
			'grid' => $this,
			'attribute' => $matches[1],
			'format' => isset($matches[3]) ? $matches[3] : 'text',
			'label' => isset($matches[5]) ? $matches[5] : null,
		]);
	}

	protected function guessColumns()
	{
		$models = $this->dataProvider->getModels();
		$model = reset($models);
		if (is_array($model) || is_object($model)) {
			foreach ($model as $name => $value) {
				$this->columns[] = $name;
			}
		} else {
			throw new InvalidConfigException('Unable to generate columns from data.');
		}
	}
}
