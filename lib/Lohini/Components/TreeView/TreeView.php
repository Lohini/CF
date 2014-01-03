<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\TreeView;
/**
 * TreeView control
 *
 * Copyright (c) 2009 Roman Novák (http://romcok.eu)
 *
 * This source file is subject to the New-BSD licence.
 *
 * For more information please see http://nettephp.com
 *
 * @author     Roman Novák
 * @copyright  Copyright (c) 2009, 2010 Roman Novák
 * @license    New-BSD
 * @link       http://nettephp.com/cs/extras/treeview
 * @version    0.6.0a
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

use Lohini\Database\DataSources\IDataSource;

/**
 * TreeView Control
 */
class TreeView
extends TreeViewNode
{
	const AJAX=0;
	const EXPANDED=1;
	/******************** variables ********************/
	/** @var callable */
	public $onNodeRender;
	/** @var callable */
	public $onFetchDataSource;
	/** @var bool */
	public $useAjax=TRUE;
	/** @var bool */
	public $isSortable=FALSE;
	/** @var bool */
	public $rememberState=TRUE;
	/** @var bool */
	public $recursiveMode=FALSE;
	/** @var string */
	public $labelColumn='name';
	/** @var string */
	public $primaryKey='id';
	/** @var string */
	public $parentColumn='parentId';
	/** @var string */
	public $startParent;
	/** @var ITreeViewRenderer */
	protected $renderer;
	/** @var IDataSource */
	protected $dataSource;
	/** @var int */
	protected $mode=0;
	/** @var array used for expanded mode */
	protected $dataRows;
	/** @var string */
	protected $selected;

	/**
	 * Adds link
	 *
	 * @param string link destination
	 * @param string labelKey
	 * @param string paramKey
	 * @param bool useAjax
	 * @param bool presenterComponent
	 * @return TreeViewLink
	 */
	public function addLink($destination='this', $labelKey='name', $paramKey=NULL, $useAjax=FALSE, $presenterComponent=NULL)
	{
		if ($paramKey===NULL) {
			$paramKey=$this->primaryKey;
			}
		if (!empty($this->parent) && empty($presenterComponent)) {
			$presenterComponent=$this->parent;
			}
		return $this['nodeLink']=new TreeViewLink($destination, $labelKey, $paramKey, $useAjax, $presenterComponent);
	}

	/**
	 * Sets data source
	 *
	 * @param mixed data source
	 */
	function setDataSource(IDataSource $dataSource)
	{
		if (!$dataSource instanceof IDataSource) {
			throw new \InvalidArgumentException('DataSource must implement \Lohini\Database\DataSources\IDataSource');
			}
		$this->dataSource=$dataSource;
	}

	/**
	 * Gets data source
	 *
	 * @return \Lohini\Database\DataSources\IDataSource
	 */
	function getDataSource()
	{
		return $this->dataSource;
	}

	/**
	 * @return \ArrayIterator
	 */
	protected function getDataRows()
	{
		if (TreeView::EXPANDED===$this->mode) {
			if ($this->dataRows===NULL) {
				$this->dataRows=$this->dataSource->fetchAssoc($this->primaryKey);
				}
			return $this->dataRows;
			}
		return parent::getDataRows();
	}

	/******************** rendering ********************/
	/**
	 * @param Renderers\IRenderer $renderer
	 */
	public function setRenderer(Renderers\IRenderer $renderer)
	{
		$this->renderer=$renderer;
	}

	public function getRenderer()
	{
		if ($this->renderer===NULL) {
			$this->renderer= $this->isSortable
				? new Renderers\Sortable
				: new Renderers\Conventional;
			}
		return $this->renderer;
	}

	public function render()
	{
		$this->load();
		$args=func_get_args();
		array_unshift($args, $this);
		echo call_user_func_array([$this->getRenderer(), 'render'], $args);
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		$this->load();
		$args=func_get_args();
		array_unshift($args, $this);
		return call_user_func_array([$this->getRenderer(), 'render'], $args);
	}

	/**
	 * @return int
	 */
	public function getState()
	{
		if ($this->state==NULL) {
			$this->state=self::EXPANDED;
			}
		return $this->state;
	}

	/**
	 * @return TreeView
	 */
	public function getTreeView()
	{
		return $this;
	}

	/**
	 * @return int
	 */
	public function getMode()
	{
		return $this->mode;
	}

	/**
	 * @param int $mode
	 */
	public function setMode($mode)
	{
		$this->mode=(int)$mode;
	}

	/**
	 * Sets a selected item
	 *
	 * @param string $selected
	 */
	function setSelected($selected)
	{
		$this->selected=$selected;
	}

	/**
	 * Returns selected item
	 *
	 * @return string
	 */
	function getSelected()
	{
		return $this->selected;
	}
}
