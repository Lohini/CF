<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\DataGrid\Renderers;

use Nette\Utils\Html,
	Lohini\Components\DataGrid\Columns\ActionColumn,
	Lohini\Components\DataGrid\Columns\Column;

/**
 * Twitter Bootstrap3 version of conventional renderer
 *
 * @author Lopo <lopo@lohini.net>
 */
class TwBs3
extends Conventional
{
	/** @var array of HTML tags */
	public $wrappers=[
		'datagrid' => [
			'container' => 'table class="datagrid table table-bordered table-hover table-condensed"',
			],
		'form' => [
			'.class' => 'datagrid',
			],
		'error' => [
			'container' => 'ul class="error danger"',
			'item' => 'li',
			],
		'row.header' => [
			'container' => 'tr class=header',
			'cell' => [
				'container' => 'th', // .checker, .actions
				'checker' => 'span class="invert glyphicon glyphicon-random" title="Invert" style="cursor: pointer;"',
				'.active' => 'info',
				],
			],
		'row.filter' => [
			'container' => 'tr class=filters',
			'cell' => [
				'container' => 'td', // .actions
				],
			'control' => [
				'.input' => 'text',
				'.select' => 'select',
				'.submit' => 'button btn',
				],
			],
		'row.content' => [
			'container' => 'tr', // .even, .selected
			'.even' => 'even',
			'cell' => [
				'container' => 'td', // .checker, .actions
				],
			],
		'row.footer' => [
			'container' => 'tr class=footer',
			'cell' => [
				'container' => 'td',
				],
			],
		'paginator' => [
			'container' => 'span class=paginator',
			'button' => [
				'first' => 'span class="paginator-first glyphicon glyphicon-step-backward"',
				'prev' => 'span class="paginator-prev glyphicon glyphicon-chevron-left"',
				'next' => 'span class="paginator-next glyphicon glyphicon-chevron-right"',
				'last' => 'span class="paginator-last glyphicon glyphicon-step-forward"',
				],
			'controls' => [
				'container' => 'span class=paginator-controls',
				],
			],
		'operations' => [
			'container' => 'span class=operations',
			],
		'info' => [
			'container' => 'span class=grid-info',
			],
		];


	/**
	 * Renders data grid paginator
	 *
	 * @return string
	 */
	public function renderPaginator()
	{
		$paginator=$this->dataGrid->paginator;
		if ($paginator->pageCount<=1) {
			return '';
			}

		$container=$this->getWrapper('paginator container');

		$a=Html::el('a')
			->addClass(\Lohini\Components\DataGrid\Action::$ajaxClass);

		// to-first button
		$first=$this->getWrapper('paginator button first');
		$title=$this->dataGrid->translate('First');
		$link=clone $a->href($this->dataGrid->link('page', 1));
		if ($first instanceof Html) {
			if ($paginator->isFirst()) {
				$first->addClass('inactive');
				}
			else {
				$first=$link->add($first);
				}
			$first->title($title);
			}
		else {
			$first=$link->setText($title);
			}
		$container->add($first);

		// previous button
		$prev=$this->getWrapper('paginator button prev');
		$title=$this->dataGrid->translate('Previous');
		$link=clone $a->href($this->dataGrid->link('page', $paginator->page-1));
		if ($prev instanceof Html) {
			if ($paginator->isFirst()) {
				$prev->addClass('inactive');
				}
			else {
				$prev=$link->add($prev);
				}
			$prev->title($title);
			}
		else {
			$prev=$link->setText($title);
			}
		$container->add($prev);

		// page input
		$controls=$this->getWrapper('paginator controls container');
		$form=$this->dataGrid->getForm(TRUE);
		$format=$this->dataGrid->translate($this->paginatorFormat);
		$html=str_replace(
				['%label%', '%input%', '%count%'],
				[$form['page']->label, $form['page']->control, $paginator->pageCount],
				$format
				);
		$controls->add(Html::el()->setHtml($html));
		$container->add($controls);

		// next button
		$next=$this->getWrapper('paginator button next');
		$title=$this->dataGrid->translate('Next');
		$link=clone $a->href($this->dataGrid->link('page', $paginator->page+1));
		if ($next instanceof Html) {
			if ($paginator->isLast()) {
				$next->addClass('inactive');
				}
			else {
				$next=$link->add($next);
				}
			$next->title($title);
			}
		else {
			$next=$link->setText($title);
			}
		$container->add($next);

		// to-last button
		$last=$this->getWrapper('paginator button last');
		$title=$this->dataGrid->translate('Last');
		$link=clone $a->href($this->dataGrid->link('page', $paginator->pageCount));
		if ($last instanceof Html) {
			if ($paginator->isLast()) {
				$last->addClass('inactive');
				}
			else {
				$last=$link->add($last);
				}
			$last->title($title);
			}
		else {
			$last=$link->setText($title);
			}
		$container->add($last);

		// page change submit
		$control=$form['pageSubmit']->control;
		$control->title=$control->value;
		$control->addClass('btn btn-xs');
		$container->add($control);

		unset($first, $prev, $next, $last, $paginator, $link, $a, $form);
		return $container->render();
	}

	/**
	 * Generates datagrid header
	 *
	 * @return Html
	 */
	protected function generateHeaderRow()
	{
		$row=$this->getWrapper('row.header container');

		// checker
		if ($this->dataGrid->hasOperations()) {
			$cell=$this->getWrapper('row.header cell container')->addClass('checker');
			$cell->add($this->getWrapper('row.header cell checker'));

			if ($this->dataGrid->hasFilters()) {
				$cell->rowspan(2);
				}
			$row->add($cell);
		}

		// headers
		foreach ($this->dataGrid->getColumns() as $column) {
			$value=$text=$column->caption;

			if ($column->isOrderable()) {
				$i=1;
				parse_str($this->dataGrid->order, $list);
				foreach ($list as $field => $dir) {
					$list[$field]=[$dir, $i++];
					}

				if (isset($list[$column->getName()])) {
					$a= $list[$column->getName()][0]==='a';
					$d= $list[$column->getName()][0]==='d';
					}
				else {
					$a= $d= FALSE;
					}

				if (count($list)>1 && isset($list[$column->getName()])) {
					$text.=Html::el('span')->setHtml($list[$column->getName()][1]);
					}

				$up= clone $down= Html::el('a')->addClass(Column::$ajaxClass);
				$up->addClass($a? 'active' : '')
						->href($column->getOrderLink('a'))
						->add(Html::el('span')
							->class('up glyphicon glyphicon-chevron-up')
							->style('float: right;')
							);
				$down->addClass($d? 'active' : '')
						->href($column->getOrderLink('d'))
						->add(Html::el('span')
							->class('down glyphicon glyphicon-chevron-down')
							->style('float: right;')
							);
				$positioner=Html::el('span')->class('positioner')->add($up)->add($down);
				$active= $a || $d;

				$value=(string)Html::el('a')->href($column->getOrderLink())
						->addClass(Column::$ajaxClass)->setHtml($text).$positioner;
				}
			else {
				$value=(string)Html::el('p')->setText($value);
				}

			$cell=$this->getWrapper('row.header cell container')->setHtml($value);
			$cell->attrs=$column->getHeaderPrototype()->attrs;
			$cell->addClass($this->getValue('row.header cell .class'));
			$cell->addClass(isset($active) && $active==TRUE ? $this->getValue('row.header cell .active') : NULL);
			if ($column instanceof ActionColumn) {
				$cell->addClass('actions');
				}
			$row->add($cell);
			}
		return $row;
	}
}
