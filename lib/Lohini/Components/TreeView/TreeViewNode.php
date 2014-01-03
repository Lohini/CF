<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\TreeView;
/**
 * TreeView node.
 *
 * @author     Roman Novák
 * @copyright  Copyright (c) 2009, 2010 Roman Novák
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

use Lohini\Database\DataSources\IDataSource;

class TreeViewNode
extends \Nette\Application\UI\Control
{
	const COLLAPSED=0;
	const EXPANDED=1;
	/** @var mixed */
	protected $dataRow;
	/** @var int */
	protected $state;
	/** @var bool */
	protected $loaded=FALSE;
	/** @var bool */
	protected $invalid=FALSE;


	/**
	 * @param \Nette\ComponentModel\IContainer $parent
	 * @param string $name
	 * @param mixed $dataRow
	 */
	function __construct(\Nette\ComponentModel\IContainer $parent=NULL, $name=NULL, &$dataRow=NULL)
	{
		$this->setDataRow($dataRow);
		parent::__construct($parent, $name);
	}

    /********** handlers **********/
	function handleExpand()
	{
		$this->invalidate();
		$this->expand();
	}

	function handleCollapse()
	{
		$this->invalidate();
		$this->collapse();
	}

	/**
	 * @return \ArrayIterator
	 * @throws \Nette\InvalidStateException
	 */
	protected function getDataRows()
	{
		$ds=clone $this->treeView->dataSource;
		if (!empty($this->treeView->onFetchDataSource)) {
			$this->treeView->onFetchDataSource($this, $ds);
			}
		if ($ds===NULL) {
			throw new \Nette\InvalidStateException('Missing data source.');
			}
		if (!($ds instanceof IDataSource)) {
			throw new \Nette\InvalidStateException('DataSource must implement \Lohini\Database\DataSources\IDataSource interface.');
			}
		if ($this->getParent() instanceof TreeViewNode && !empty($this->dataRow)) {
			$ds->filter($this->treeView->parentColumn, IDataSource::EQUAL, $this->dataRow[$this->treeView->primaryKey]);
			}
		else {
			if ($this->treeView->startParent) {
				$ds->filter($this->treeView->parentColumn, IDataSource::EQUAL, $this->treeView->startParent);
				}
			else {
				$ds->filter($this->treeView->parentColumn, IDataSource::IS_NULL);
				}
			}
		return $ds->getIterator();
	}

	protected function load()
	{
		if (!$this->loaded) {
			$this->loaded=TRUE;
			$pid=$this->treeView->parentColumn;
			$dataRows= TreeView::EXPANDED!==$this->treeView->mode
					? $this->getDataRows()
					: $this->treeView->getDataRows();
			foreach ($dataRows as $dataRow) {
				if (empty($this->dataRow)
					|| (!empty($this->dataRow) && $this->dataRow['id']===$dataRow[$pid])
					) {
					$node=new TreeViewNode($this, $dataRow[$this->treeView->primaryKey], $dataRow);
					$node['nodeLink']=clone $this['nodeLink'];
					if (TreeView::EXPANDED===$this->treeView->mode
						&& (($this->treeView->rememberState && !$node->isSessionState()) || !$this->treeView->rememberState)
						) {
						$node->expand();
						}
					}
				}
			}
	}

	/**
	 * @param string $signal
	 */
	public function signalReceived($signal)
	{
		if (($parent=$this->getParent()) instanceof TreeViewNode) {
			$parent->expand();
			}
		parent::signalReceived($signal);
	}

	/**
	 * @param string $name
	 * @return \Nette\ComponentModel\IComponent
	 */
	protected function createComponent($name)
	{
		$this->load();
		return parent::createComponent($name);
	}

	/**
	 * @param string $name
	 * @return \TreeViewLink
	 */
	protected function createComponentStateLink($name)
	{
		switch ($this->getState()) {
			case self::EXPANDED:
				$destination='collapse';
				$labelKey='-';
				break;
			case self::COLLAPSED:
				$destination='expand';
				$labelKey='+';
				break;
			}
		return new TreeViewLink($destination, $labelKey, NULL, $this->getTreeView()->useAjax, $this);
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getNodes()
	{
		$this->load();
		return $this->getComponents(FALSE, 'Lohini\Components\TreeView\TreeViewNode');
	}

	function expand()
	{
		$this->setState(self::EXPANDED);
	}

	function collapse()
	{
		$this->setState(self::COLLAPSED);
	}

	/********** state **********/
	/**
	 * @param int $state
	 */
	public function setState($state)
	{
		$this->state=$state;
		if ($this->getTreeView()->rememberState) {
			$session=$this->getNodeSession();
			$session['state']=$state;
			}
	}

	/**
	 * @return int
	 */
	public function getState()
	{
		if ($this->state===NULL) {
			if ($this->getTreeView()->rememberState===TRUE) {
				$session=$this->getNodeSession();
				$this->state= isset($session['state'])? $session['state'] : self::COLLAPSED;
				}
			else {
				$this->state=self::COLLAPSED;
				}
			}
		return $this->state;
	}

	/**
	 * @return bool
	 */
	public function isSessionState()
	{
		$session=$this->getNodeSession();
		return isset($session['state']);
	}

	/**
	 * @return \Nette\Http\Session|\Nette\Http\SessionSection
	 */
	protected function getNodeSession()
	{
		return $this->getPresenter()->getSession('Lohini.TreeView/'.$this->getTreeView()->getName().'/'.$this->getName());
	}

	/********** node validation **********/
	public function invalidate()
	{
		$this->invalid=TRUE;
		$this->invalidateControl();
	}

	public function validate()
	{
		$this->invalid=FALSE;
		$this->validateControl();
	}

	/**
	 * @return bool
	 */
	public function isInvalid()
	{
		return $this->invalid;
	}

	/**
	 * @return bool
	 */
	public function isLoaded()
	{
		return $this->loaded;
	}

	/********** setters **********/
	/**
	 * @param mixed $dataRow
	 */
	function setDataRow($dataRow)
	{
		$this->dataRow=$dataRow;
	}

	/********** getters **********/
	/**
	 * @return \Nette\ComponentModel\IComponent
	 */
	public function getTreeView()
	{
		return $this->lookup('Lohini\Components\TreeView\TreeView');
	}

	/**
	 * @return mixed
	 */
	function getDataRow()
	{
		return $this->dataRow;
	}
}
