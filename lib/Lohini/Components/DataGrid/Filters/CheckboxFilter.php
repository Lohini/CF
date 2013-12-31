<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2013 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\DataGrid\Filters;
/**
 * @author     Roman Sklenář
 * @copyright  Copyright (c) 2009 Roman Sklenář (http://romansklenar.cz)
 * @license    New BSD License
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

/**
 * Representation of data grid column checkbox filter
 */
class CheckboxFilter
extends ColumnFilter
{
	/**
	 * Returns filter's form element
	 *
	 * @return \Nette\Forms\Controls\BaseControl
	 */
	public function getFormControl()
	{
		if ($this->element instanceof \Nette\Forms\Controls\BaseControl) {
			return $this->element;
			}
		return $this->element=new \Lohini\Forms\Controls\Checkbox3S($this->getName());
	}
}