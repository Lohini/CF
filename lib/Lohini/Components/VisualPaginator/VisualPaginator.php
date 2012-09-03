<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2012 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\VisualPaginator;
/**
 * @author David Grudl
 * @author Filip Procházka
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

/**
 * Visual paginator control.
 */
class VisualPaginator
extends ComponentPaginator
{
	/**
	 * @persistent
	 * @var int
	 */
	public $page=1;


	/**
	 * Loads state informations.
	 *
	 * @param  array
	 */
	public function loadState(array $params)
	{
		parent::loadState($params);
		$this->setPage($this->page);
	}
}