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
	 * @var array|Formatter the formatter used to format model attribute values into displayable texts.
	 * This can be either an instance of [[Formatter]] or an configuration array for creating the [[Formatter]]
	 * instance. If this property is not set, the "formatter" application component will be used.
	 */
	public $formatter;
	/**
	 * @var array grid column configuration. Each array element represents the configuration
	 * for one particular grid column. For example,
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
	 * If a column is of class [[DataColumn]], the "class" element can be omitted.
	 *
	 * As a shortcut format, a string may be used to specify the configuration of a data column
	 * which only contains "attribute", "format", and/or "label" options: `"attribute:format:label"`.
	 * For example, the above "name" column can also be specified as: `"name:text:Name"`.
	 * Both "format" and "label" are optional. They will take default values if absent.
	 */
	public $columns = [];
	public $emptyCell = '&nbsp;';
	/**
	 * @var \yii\base\Model the model that keeps the user-entered filter data. When this property is set,
	 * the grid view will enable column-based filtering. Each data column by default will display a text field
	 * at the top that users can fill in to filter the data.
	 *
	 * Note that in order to show an input field for filtering, a column must have its [[DataColumn::attribute]]
	 * property set or have [[DataColumn::filter]] set as the HTML code for the input field.
	 *
	 * When this property is not set (null) the filtering feature is disabled.
	 */
	public $filterModel;
	/**
	 * @var string|array the URL for returning the filtering result. [[Html::url()]] will be called to
	 * normalize the URL. If not set, the current controller action will be used.
	 * When the user makes change to any filter input, the current filtering inputs will be appended
	 * as GET parameters to this URL.
	 */
	public $filterUrl;
	public $filterSelector;
	/**
	 * @var string whether the filters should be displayed in the grid view. Valid values include:
	 *
	 * - [[FILTER_POS_HEADER]]: the filters will be displayed on top of each column's header cell.
	 * - [[FILTER_POS_BODY]]: the filters will be displayed right below each column's header cell.
	 * - [[FILTER_POS_FOOTER]]: the filters will be displayed below each column's footer cell.
	 */
	public $filterPosition = self::FILTER_POS_BODY;
	/**
	 * @var array the HTML attributes for the filter row element
	 */
	public $filterRowOptions = ['class' => 'filters'];

	/**
	 * Initializes the grid view.
	 * This method will initialize required property values and instantiate [[columns]] objects.
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
	 * Runs the widget.
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
	 * Returns the options for the grid view JS widget.
	 * @return array the options
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
	 * Renders the data models for the grid view.
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
	 * Renders the table header.
	 * @return string the rendering result
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
	 * Renders the table footer.
	 * @return string the rendering result
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
	 * Renders the filter.
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
	 * Renders the table body.
	 * @return string the rendering result
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
	 * Renders a table row with the given data model and key.
	 * @param mixed $model the data model to be rendered
	 * @param mixed $key the key associated with the data model
	 * @param integer $index the zero-based index of the data model among the model array returned by [[dataProvider]].
	 * @return string the rendering result
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
	 * Creates column objects and initializes them.
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
	 * Creates a [[DataColumn]] object based on a string in the format of "attribute:format:label".
	 * @param string $text the column specification string
	 * @return DataColumn the column instance
	 * @throws InvalidConfigException if the column specification is invalid
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
