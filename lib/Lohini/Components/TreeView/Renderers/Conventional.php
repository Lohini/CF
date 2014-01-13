<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\TreeView\Renderers;

/**
 * TreeView renderer.
 *
 * @author     Roman Novák
 * @copyright  Copyright (c) 2009, 2010 Roman Novák
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

use Lohini\Components\TreeView\TreeViewNode,
	Nette\Utils\Html;

/**
 * Conventional renderer
 */
class Conventional
extends \Nette\Object
implements IRenderer
{
	/** @var \Lohini\Components\TreeView\TreeView */
	protected $tree;
	/** @var array */
	public $wrappers=[
		'tree' => [
			'container' => 'div'
			],
		'nodes' => [
			'root' => 'ul style="list-style-type: none;"',
			'container' => 'ul style="list-style-type: none;"'
			],
		'node' => [
			'icon' => NULL,
			'container' => 'li',
			'.selected' => 'current',
			'.expanded' => 'expanded',
			],
		'link' => [
			'node' => 'a',
			'collapse' => 'a style="float: left; width: 16px;"',
			'expand' => 'a style="float: left; width: 16px;"',
			'.ajax' => 'ajax',
			],
		];


	/**
	 * @param \Lohini\Components\TreeView\TreeView $tree
	 */
	public function render(\Lohini\Components\TreeView\TreeView $tree)
	{
		if ($this->tree!==$tree) {
			$this->tree=$tree;
			}
		$snippetId=$this->tree->getSnippetId();
		$html=$this->renderNodes($this->tree->getNodes(), 'nodes root');
		if ($this->tree->isControlInvalid() && $this->tree->getPresenter()->isAjax()) {
			$this->tree->getPresenter()->getPayload()->snippets[$snippetId]=(string)$html;
			}
		if (!$this->tree->getPresenter()->isAjax()) {
			$treeContainer=$this->getWrapper('tree container');
			$treeContainer->id=$snippetId;
			$treeContainer->add($html);
			return $treeContainer;
			}
	}

	/**
	 * @param \ArrayIterator $nodes
	 * @param string $wrapper
	 * @return Html
	 */
	public function renderNodes($nodes, $wrapper='nodes container')
	{
		$nodesContainer=$this->getWrapper($wrapper);
		foreach ($nodes as $node) {
			if (NULL!==($child=$this->renderNode($node))) {
				$nodesContainer->add($child);
				}
			}
		return $nodesContainer;
	}

	/**
	 * @param TreeViewNode $node
	 */
	public function renderNode(TreeViewNode $node)
	{
		$nodes=$node->getNodes();
		$ncount=$nodes->count()-1;
		$snippetId=$node->getSnippetId();
		$nodeContainer=$this->getWrapper('node container');
		$nodeContainer->id=$snippetId;
		if ($this->tree->getSelected()==$node->name) {
			$nodeContainer->addClass($this->getValue('node .selected'));
			}
		if ($node->getState()==TreeViewNode::EXPANDED && $ncount) {
			$nodeContainer->addClass($this->getValue('node .expanded'));
			}
		if ($ncount) {
			switch ($node->getState()) {
				case TreeViewNode::EXPANDED:
					$stateLink=$this->renderLink($node, 'stateLink', 'link collapse');
					break;
				case TreeViewNode::COLLAPSED:
					$stateLink=$this->renderLink($node, 'stateLink', 'link expand');
					break;
				}
			if (NULL!==$stateLink) {
				$nodeContainer->add($stateLink);
				}
			}
		elseif (NULL!==($icon=$this->getWrapper('node icon'))) {
			$nodeContainer->add($icon);
			}
		if (NULL!==($link=$this->renderLink($node, 'nodeLink'))) {
			$nodeContainer->add($link);
			}
		$this->tree->onNodeRender($this->tree, $node, $nodeContainer);
		if (TreeViewNode::EXPANDED===$node->getState() && $ncount) {
			$nodesContainer=$this->renderNodes($nodes);
			if (NULL!==$nodesContainer) {
				$nodeContainer->add($nodesContainer);
				}
			}
		$html=isset($nodeContainer)? $nodeContainer : $nodesContainer;
		if ($node->isInvalid()) {
			$this->tree->getPresenter()->getPayload()->snippets[$snippetId]=(string)$html;
			}
		return $html;
	}

	/**
	 * @param TreeViewNode $node
	 * @param string $name
	 * @param string $wrapper
	 * @return Html
	 */
	public function renderLink(TreeViewNode $node, $name, $wrapper='link node')
	{
		if (NULL===($el=$this->getWrapper($wrapper))) {
			return NULL;
			}
		$link=$node[$name];
		if ($link->useAjax) {
			$el->addClass($this->getValue('link .ajax'));
			}
		$el->setText($link->getLabel());
		$el->href($link->getUrl());
		if ($name!='nodeLink') {
			$span=Html::el('span');
			$span->class='collapsable';
			$span->add($el);
			return $span;
			}
		return $el;
	}

	/**
	 * @param string $name
	 * @return Html
	 */
	protected function getWrapper($name)
	{
		$data=$this->getValue($name);
		if (empty($data)) {
			return $data;
			}
		return $data instanceof Html
			? clone $data
			: Html::el($data);
	}

	protected function getValue($name)
	{
		$name=explode(' ', $name);
		$data=&$this->wrappers[$name[0]][$name[1]];
		return $data;
	}
}
