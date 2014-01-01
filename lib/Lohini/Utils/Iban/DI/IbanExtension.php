<?php // vim: ts=4 sw=4 ai:
/**
 * This file is part of Lohini (http://lohini.net)
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */
namespace Lohini\Utils\Iban\DI;

/**
 * @author Lopo <lopo@lohini.net>
 */
class IbanExtension
extends \Nette\DI\CompilerExtension
{
	public $defaults=[
		'sourceUrl' => 'http://www.swift.com/dsp/resources/documents/IBAN_Registry.txt'
		];


	public function loadConfiguration()
	{
		$builder=$this->getContainerBuilder();
		$config=$this->getConfig(['debug' => $builder->parameters['debugMode']]);

		$builder->parameters[$this->prefix('debug')]=!empty($config['debug']);

		$config=$this->resolveConfig($config, $this->defaults);

		$builder->addDefinition($this->prefix('registry'))
				->setClass('Lohini\Utils\Iban\Registry', ['@cacheStorage', 'Lohini.Iban'])
				->addSetup('$sourceUrl', [$config['sourceUrl']])
				->setInject(FALSE)
				;
		$builder->addDefinition($this->prefix('verifier'))
				->setClass('Lohini\Utils\Iban\Verifier', [$this->prefix('@registry')])
				->setInject(FALSE)
				;
		$this->loadConsole();
	}

	/**
	 * @param $provided
	 * @param $defaults
	 * @param $diff
	 * @return array
	 */
	private function resolveConfig(array $provided, array $defaults, array $diff=[])
	{
		return $this->getContainerBuilder()->expand(
			\Nette\DI\Config\Helpers::merge(
				array_diff_key($provided, array_diff_key($diff, $defaults)),
				$defaults
				)
			);
	}

	protected function loadConsole()
	{
		$builder=$this->getContainerBuilder();
		foreach ($this->loadFromFile(__DIR__.'/console.neon') as $i => $command) {
			$cli=$builder->addDefinition($this->prefix('cli.'.$i))
					->addTag(\Kdyby\Console\DI\ConsoleExtension::COMMAND_TAG);
			if (is_string($command)) {
				$cli->setClass($command);
				}
			else {
				throw new \Lohini\Utils\Iban\IbanException('Not supported');
				}
			}
	}

	/**
	 * @param string $name
	 */
	private function loadConfig($name)
	{
		$this->compiler->parseServices(
			$this->getContainerBuilder(),
			$this->loadFromFile(__DIR__.'/config/'.$name.'.neon'),
			$this->prefix($name)
			);
	}
}
