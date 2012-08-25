<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini
 *
 * @copyright (c) 2010, 2012 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Components\Header;
/**
 * @author Filip Procházka <filip.prochazka@kdyby.org>
 */
/**
 * Lohini port
 * @author Lopo <lopo@lohini.net>
 */

use Lohini\Templating\LatteHelpers,
	Nette\Application\UI,
	Nette\Latte;

/**
 */
class HeadMacro
extends \Nette\Object
implements Latte\IMacro
{
	/** @var string[] */
	private $prolog=array();
	/** @var string[] */
	private $epilog=array();


	/**
	 * @param Latte\Compiler $compiler
	 * @return HeadMacro
	 */
	public static function install(Latte\Compiler $compiler)
	{
		$me=new static($compiler);
		$compiler->addMacro('head', $me);

		$compiler->addMacro('javascript', $me);
		$compiler->addMacro('js', $me);

		return $me;
	}

	/**
	 * Initializes before template parsing.
	 */
	public function initialize()
	{
	}

	/**
	 * Finishes template parsing.
	 *
	 * @return array(prolog, epilog)
	 */
	public function finalize()
	{
		$prolog=$this->prolog;
		$epilog=$this->epilog;
		$this->prolog= $this->epilog= array();
		return array(
			implode("\n", $prolog),
			implode("\n", $epilog)
			);
	}

	/**
	 * @param Latte\MacroNode $node
	 * @return bool|string
	 */
	public function nodeOpened(Latte\MacroNode $node)
	{
		if (in_array($node->name, array('js', 'javascript'))) {
			if (($node->data->inline=empty($node->args)) && $node->htmlNode) {
				$node->data->type= in_array($node->name, array('js', 'javascript'))? 'js' : 'css';
				$node->openingCode='<?php ob_start(); ?>';
				return;
				}
			return FALSE;
			}

		$node->openingCode='<?php Lohini\Components\Header\HeadMacro::documentBegin(); ?>';
	}

	/**
	 * @param Latte\MacroNode $node
	 * @return string
	 */
	public function nodeClosed(Latte\MacroNode $node)
	{
		if (!empty($node->data->inline)) {
			$node->closingCode='<?php '.get_called_class().'::tagCaptureEnd($presenter); ?>';
			return;
			}

		$class=get_called_class();
		$writer=Latte\PhpWriter::using($node);
		if ($args=LatteHelpers::readArguments($node->tokenizer, $writer)) {
			$this->prolog[]=\Nette\Utils\PhpGenerator\Helpers::formatArgs($class.'::headArgs($presenter, ?);', array($args));
			}

		$this->epilog[]='$_documentBody='.$class.'::documentEnd();';
		$this->epilog[]=$class.'::headBegin($presenter); ?>';
		$this->epilog[]=$this->wrapTags($node->content, $writer);
		$this->epilog[]='<?php '.$class.'::headEnd($presenter);';
		$this->epilog[]='echo $_documentBody;';

		$node->content=NULL;
	}

	/**
	 * @param string $content
	 * @param Latte\PhpWriter $writer
	 * @return string
	 */
	private function wrapTags($content, Latte\PhpWriter $writer)
	{
		return LatteHelpers::wrapTags(\Nette\Templating\Helpers::optimizePhp($content),
			$writer->write('<?php ob_start(); ?>'),
			$writer->write('<?php '.get_called_class().'::tagCaptureEnd($presenter); ?>')
			);
	}

	/**
	 */
	public static function documentBegin()
	{
		ob_start();
	}

	/**
	 */
	public static function documentEnd()
	{
		return ob_get_clean();
	}

	/**
	 * @param UI\Presenter $presenter
	 */
	public static function headBegin(UI\Presenter $presenter)
	{
		$head = static::getHead($presenter);
		echo $head->getElement()->startTag();
	}

	/**
	 * @param UI\Presenter $presenter
	 */
	public static function headEnd(UI\Presenter $presenter)
	{
		$head=static::getHead($presenter);
		$head->renderContent();
		echo $head->getElement()->endTag();
	}

	/**
	 * @param UI\Presenter $presenter
	 */
	public static function tagCaptureEnd(UI\Presenter $presenter)
	{
		$content=ob_get_clean();
		$tag=\Nette\Utils\Html::el(substr($content, 1, ($i=strpos($content, '>'))? $i-1 : NULL));
		$head=static::getHead($presenter);

		if ($tag->getName()==='meta') {
			$head->addMeta($tag->attrs);
			}
		elseif ($tag->getName()==='link' && $tag->attrs['rel']==='shortcut icon') {
			$head->setFavicon($tag);
			}
		elseif ($tag->getName()==='script') {
			$head->addAssetSource('js', $content);
			}
		else {
			$head->addTag($tag);
			}
	}

	/************************ Helpers ************************/
	/**
	 * @param UI\PresenterComponent $control
	 * @return HeaderControl
	 * @throws \Nette\InvalidStateException
	 */
	private static function getHead(UI\PresenterComponent $control)
	{
		/** @var \Nette\Application\UI\Presenter $presenter */
		$presenter=$control->getPresenter();
		$components=$presenter->getComponents(FALSE, 'Lohini\Components\Header\HeaderControl');
		if (!$headerControl=iterator_to_array($components)) {
			throw new \Nette\InvalidStateException(
				'Please register Lohini\Components\Header\HeaderControl as component in presenter.'
				.'If you have the component registered and this error keeps returning, try to instantiate it manually.'
				);
			}

		return reset($headerControl);
	}

	/**
	 * @param UI\Presenter $presenter
	 * @param array $args
	 */
	public static function headArgs(UI\Presenter $presenter, array $args)
	{
		$head=static::getHead($presenter);
		if (isset($args['title'])) {
			$head->defaultTitle=(array)$args['title'];
			}
	}
}
