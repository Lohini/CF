<?php // vim: ts=4 sw=4 ai:
namespace Lohini\Utils\Iban\Console;
/**
 * This file is part of Lohini (http://lohini.net)
 *
 * @copyright (c) 2010, 2014 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */

use Symfony\Component\Console\Command\Command,
	Symfony\Component\Console\Input\InputInterface,
	Symfony\Component\Console\Output\OutputInterface,
	Lohini\Utils\Iban\Registry;

/**
 * Show infomation about internal registry/cache
 *
 * @author Lopo <lopo@lohini.net>
 */
class InfoCommand
extends Command
{
	protected function configure()
	{
		$this->setName('lohini:iban:info')
			->setDescription('IBAN registry status info');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return NULL|int NULL or 0 if everything went fine, or an error code
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try {
			/** @var \Lohini\Utils\Iban\Registry $registry */
			$registry=$this->getHelper('container')->getByType('Lohini\Utils\Iban\Registry');

			$output->writeln('source URL: '.$registry->info[Registry::SOURCE]);
			$output->writeln('ETag: '.$registry->info[Registry::ETAG]);
			}
		catch (\Exception $ex) {
			$output->writeln('error:');
			$output->writeln($ex->getMessage());
			return 1;
			}
	}
}
