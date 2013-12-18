<?php // vim: ts=4 sw=4 ai:
namespace Lohini\Utils\Iban\Console;
/**
 * This file is part of Lohini (http://lohini.net)
 *
 * @copyright (c) 2010, 2013 Lopo <lopo@lohini.net>
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License Version 3
 */

use Symfony\Component\Console\Command\Command,
	Symfony\Component\Console\Input\InputInterface,
	Symfony\Component\Console\Output\OutputInterface,
	Symfony\Component\Console\Input\InputOption;

/**
 * Dump source TXT
 *
 * @author Lopo <lopo@lohini.net>
 */
class DumpCommand
extends Command
{
	protected function configure()
	{
		$this->setName('lohini:iban:dump')
			->setDescription('dump IBAN registry source')
			->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'filename for save');
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
			$data=$registry->getData();
			if ($filename=$input->getOption('file')) {
				if (FALSE===file_put_contents($filename, $data)) {
					throw new \Exception('error writing to file');
					}
				$output->writeln("IBAN source data saved to '$filename'");
				return 0;
				}
			$output->write($data, FALSE, OutputInterface::OUTPUT_RAW);
			}
		catch (\Exception $ex) {
			$output->writeln('error:');
			$output->writeln($ex->getMessage());
			return 1;
			}
	}
}
