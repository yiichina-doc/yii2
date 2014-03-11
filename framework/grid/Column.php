<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @translate francis.tm <francis.tm@me.com>
 */

namespace yii\grid;

use Closure;
use yii\base\Object;
use yii\helpers\Html;

/**
 * Column 是 [[GridView]] 中所有列的基本类
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Column extends Object
{
  /**
   * @var GridView 当前列所归属的 GridView 对象。
   */
  public $grid;
  /**
   * @var string 列头部单元格的内容。注意内容中包含的HTML字符将不会被转义。
   */
  public $header;
  /**
   * @var string 列尾部的内容。注意内容中包含的HTML字符将不会被转义。
   */
  public $footer;
  /**
   * @var callable
   */
  public $content;
  /**
   * @var boolean 此列是否可见。默认为true。
   */
  public $visible = true;
  public $options = [];
  public $headerOptions = [];
  /**
   * @var array|\Closure
   */
  public $contentOptions = [];
  public $footerOptions = [];
  /**
   * @var array 此列的HTML标签属性。
   */
  public $filterOptions = [];


  /**
   * 渲染列头部单元格。
   */
  public function renderHeaderCell()
  {
    return Html::tag('th', $this->renderHeaderCellContent(), $this->headerOptions);
  }

  /**
   * 渲染列尾部单元格。
   */
  public function renderFooterCell()
  {
    return Html::tag('td', $this->renderFooterCellContent(), $this->footerOptions);
  }

  /**
   * 渲染一个数据单元格。
   * @param mixed $model 将要被渲染的数据模型
   * @param mixed $key 该列与将要渲染模型相关联的键值
   * @param integer $index 一个从0起始的数组键值索引，由 [[GridView::dataProvider]] 返回所得。
   * @return string 渲染后的结果
   */
  public function renderDataCell($model, $key, $index)
  {
    if ($this->contentOptions instanceof Closure) {
      $options = call_user_func($this->contentOptions, $model, $key, $index, $this);
    } else {
      $options = $this->contentOptions;
    }
    return Html::tag('td', $this->renderDataCellContent($model, $key, $index), $options);
  }

  /**
   * 渲染筛选单元格。
   */
  public function renderFilterCell()
  {
    return Html::tag('td', $this->renderFilterCellContent(), $this->filterOptions);
  }

  /**
   * 渲染列头部单元格内容。
   * 默认的实现方法会单纯的渲染 [[header]] 里的内容。
   * 此方法有可能因为自定义了列头部而被重写。
   * @return string 渲染结果
   */
  protected function renderHeaderCellContent()
  {
    return trim($this->header) !== '' ? $this->header : $this->grid->emptyCell;
  }

  /**
   * 渲染列尾部单元格内容。
   * 默认的实现方法会单纯的渲染 [[footer]] 里的内容。
   * 此方法有可能因为自定义了列尾部而被重写。
   * @return string 渲染结果
   */
  protected function renderFooterCellContent()
  {
    return trim($this->footer) !== '' ? $this->footer : $this->grid->emptyCell;
  }

  /**
   * 返回数据单元格的原始内容。
   * 当渲染数据单元格的时候，此方法会由 [[renderDataCellContent()]] 调用。
   * @param mixed $model 数据模型
   * @param mixed $key 该列与将要渲染模型相关联的键值
   * @param integer $index 一个从0起始的数组键值索引，由 [[GridView::dataProvider]] 返回所得。
   * @return string 渲染结果
   */
  protected function getDataCellContent($model, $key, $index)
  {
    if ($this->content !== null) {
      return call_user_func($this->content, $model, $key, $index, $this);
    } else {
      return null;
    }
  }

  /**
   * 渲染数据单元格内容。
   * @param mixed $model 数据模型
   * @param mixed $key 该列与将要渲染模型相关联的键值
   * @param integer $index 一个从0起始的数组键值索引，由 [[GridView::dataProvider]] 返回所得。
   * @return string 渲染结果
   */
  protected function renderDataCellContent($model, $key, $index)
  {
    return $this->content !== null ? $this->getDataCellContent($model, $key, $index) : $this->grid->emptyCell;
  }

  /**
   * 渲染筛选单元格内容。
   * 此方法默认的实现为渲染一个空格。
   * 此方法可能会因为自定义筛选单元格的内容而被重写。
   * @return string 渲染结果
   */
  protected function renderFilterCellContent()
  {
    return $this->grid->emptyCell;
  }
}
