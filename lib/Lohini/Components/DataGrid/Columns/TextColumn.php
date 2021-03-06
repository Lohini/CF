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

use Nette\Utils\Strings;

/**
 * Representation of textual data grid column
 */
class TextColumn
extends Column
{
	/**
	 * Formats cell's content
	 *
	 * @param mixed $value
	 * @param \DibiRow|array $data
	 * @return string
	 */
	public function formatContent($value, $data=NULL)
	{
		$value=htmlSpecialChars($value);

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

		// truncate
		if ($this->maxLength!=0) {
			if ($value instanceof \Nette\Utils\Html) {
				$value->setText(Strings::truncate($value->getText(), $this->maxLength));
				}
			else {
				$value=Strings::truncate($value, $this->maxLength);
				}
			}

		return $value;
	}

	/**
	 * Filters data source
	 *
	 * @param mixed $value
	 */
	public function applyFilter($value)
	{
		if (!$this->hasFilter()) {
			return;
			}

		$dataSource=$this->getDataGrid()->getDataSource();

		if (strpos($value, '*')!==FALSE) {
			$dataSource->filter($this->name, 'LIKE', $value); //asterisks are converted internally
			}
		elseif ($value==='NULL' || $value==='NOT NULL') {
			$dataSource->filter($this->name, "IS $value");
			}
		else {
			$dataSource->filter($this->name, 'LIKE', "*$value*");
			}
	}
}
