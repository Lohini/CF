<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\DataGrid\Columns;
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
 * Representation of date data grid column.
 */
class DateColumn
extends TextColumn
{
	/** @var string */
	public $format;
	/** @var string */
	public $func;


	/**
	 * @param string $caption column's textual caption
	 * @param string $format date format supported by $func
	 * @param string $func function to format date
	 */
	public function __construct($caption=NULL, $format='%x', $func='strftime')
	{
		parent::__construct($caption);
		$this->format=$format;
		$this->func=\Nette\Utils\Callback::check($func);
	}

	/**
	 * Formats cell's content
	 *
	 * @param mixed $value
	 * @param \DibiRow|array $data
	 * @return string
	 */
	public function formatContent($value, $data=NULL)
	{
		if (!($value instanceof \DateTime) && ((int)$value==NULL || empty($value))) {
			return $this->getDataGrid()->translate('N/A');
			}

		if (is_array($this->replacement) && !empty($this->replacement)) {
			if (in_array($value, array_keys($this->replacement))) {
				$value=$this->replacement[$value];
				}
			}

		foreach ($this->formatCallback as $callback) {
			if (is_callable($callback)) {
				$value=call_user_func($callback, $value, $data);
				}
			}

		$value= is_numeric($value)? (int)$value : ($value instanceof \DateTime ? $value->format('U') : strtotime($value));
		return call_user_func($this->func, $this->format, $value);
	}

	/**
	 * Applies filtering on dataset
	 *
	 * @param mixed $value
	 */
	public function applyFilter($value)
	{
		if (!$this->hasFilter()) {
			return;
			}

		$this->getDataGrid()->getDataSource()->filter($this->name, '=', $value, NULL, 'DATE');
	}
}
