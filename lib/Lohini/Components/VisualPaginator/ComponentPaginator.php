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
 */
class ComponentPaginator
extends Lohini\Application\UI\Control
{
	/** @var Paginator */
	private $paginator;


	/**
	 * @return Paginator
	 */
	public function getPaginator()
	{
		if ($this->paginator===NULL) {
			$this->paginator=new Paginator;
			}

		return $this->paginator;
	}

	/**
	 * Renders paginator.
	 */
	public function render()
	{
		echo $this->__toString();
	}

	/**
	 * @return string
	 */
	public function __toString()
	{
		$this->template->steps=$this->getPaginator()->getPagesListFriendly();
		$this->template->paginator=$this->getPaginator();

		if ($this->template->getFile()===NULL){
			$this->template->setFile(__DIR__.'/template.latte');
			}

		return (string)$this->template;
	}

	/**
	 * @param string $page
	 * @return ComponentPaginator (fluent)
	 */
	public function setPage($page)
	{
		$this->getPaginator()->page=$page;
		return $this;
	}

	/**
	 * @param string $file
	 * @return ComponentPaginator (fluent)
	 */
	public function setTemplateFile($file)
	{
		$this->getTemplate()->setFile($file);
		return $this;
	}
}
