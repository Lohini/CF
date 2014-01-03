<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\TreeView;
/**
 * TreeView link.
 *
 * @author     Roman Novák
 * @copyright  Copyright (c) 2009, 2010 Roman Novák
 * @package    nette-treeview
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

use Nette\Application\UI\PresenterComponent;

class TreeViewLink
extends \Nette\ComponentModel\Component
{
	/** @var PresenterComponent */
	public $presenterComponent;
	/** @var string */
	public $destination;
	/** @var string */
	public $labelKey;
	/** @var string */
	public $paramKey;
	/** @var bool */
	public $useAjax;
	/** @var string */
	public $paramSeparator='/';


	/**
	 * @param string $destination
	 * @param string $labelKey
	 * @param string $paramKey
	 * @param bool $useAjax
	 * @param PresenterComponent $presenterComponent
	 */
	public function __construct($destination, $labelKey, $paramKey, $useAjax=FALSE, PresenterComponent $presenterComponent=NULL)
	{
		$this->destination=$destination;
		$this->labelKey=$labelKey;
		$this->paramKey=$paramKey;
		$this->useAjax=$useAjax;
		$this->presenterComponent=$presenterComponent;
	}

	/**
	 * @param TreeViewNode $node
	 */
	protected function attached($node)
	{
		if ($this->presenterComponent===NULL) {
			$this->presenterComponent=$node->presenter;
			}
		parent::attached($node);
	}

	/**
	 * @return string
	 */
	public function getLabel()
	{
		$dataRow=$this->getParent()->dataRow;
		if ($this->paramKey===NULL || !isset($dataRow[$this->labelKey])) {
			return $this->labelKey;
			}
		return $dataRow[$this->labelKey];
	}

	/**
	 * @return mixed
	 */
	public function getParam()
	{
		if ($this->paramKey===NULL) {
			return NULL;
			}
		$dataRow=$this->getParent()->dataRow;
		if (!is_array($this->paramKey) && $this->getParent()->getTreeView()->recursiveMode) {
			$param='';
			$preparent=$this->getParent()->getParent();
			if ($preparent instanceof TreeViewNode && !$preparent instanceof TreeView) {
				$param.=$this->getParent()->getParent()->getComponent('nodeLink')->getParam().'/';
				}
			return $param.$dataRow[$this->paramKey];
			}
		if (is_array($this->paramKey)) {
			$param=[];
			foreach ($this->paramKey as $key) {
				$param[$key]=$dataRow[$key];
				}
			return $param;
			}
		return $dataRow[$this->paramKey];
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return NULL===($param=$this->getParam())
			? $this->presenterComponent->link($this->destination)
			: $this->presenterComponent->link($this->destination, $param);
	}
}