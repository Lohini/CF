<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Database\DataSources\Doctrine\Utils;

use Doctrine\ORM\Query\Lexer;

/**
 * SQL DATE() function
 * used for DateFilter
 *
 * @author Lopo <lopo@lohini.net>
 */
class DateFunction
extends \Doctrine\ORM\Query\AST\Functions\FunctionNode
{
	public $dateExpression=NULL;

	/**
	 * @param \Doctrine\ORM\Query\Parser $parser
	 */
	public function parse(\Doctrine\ORM\Query\Parser $parser)
	{
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);

		$this->dateExpression=$parser->ArithmeticPrimary();

		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}

	/**
	 * @param \Doctrine\ORM\Query\SqlWalker $sqlWalker
	 * @return string
	 */
	public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
	{
		return 'DATE('
				.$this->dateExpression->dispatch($sqlWalker)
				.')';
	}
}
