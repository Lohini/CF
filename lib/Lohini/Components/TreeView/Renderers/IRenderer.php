<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\TreeView\Renderers;
/**
 * TreeView renderer interface.
 *
 * @author     Roman Novák
 * @copyright  Copyright (c) 2009, 2010 Roman Novák
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

interface IRenderer
{
	/**
	 * @var \Lohini\Components\TreeView\TreeView $node
	 */
	function render(\Lohini\Components\TreeView\TreeView $node);
}
