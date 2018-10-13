<?php namespace bff\console\commands\tests;

/**
 * Консоль: команды запуска Unit-тестов
 * @version 0.1
 * @modified 9.may.2018
 * Examples:
 * Core:
 *   php bffc tests/unit
 *   php bffc tests/unit -t testName
 * Extensions:
 *   php bffc tests/unit -x plugin/name
 *   php bffc tests/unit -x plugin/name -t testName
 *   php bffc tests/unit -x theme/name
 *   php bffc tests/unit -x theme/name -t testName
 */

use bff\console\commands\Command as Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Unit extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('tests/unit')
             ->setDescription('Unit tests')
             ->addOption('--extension', '-x', InputOption::VALUE_OPTIONAL, 'Extension name: "plugin/name", "theme/name"')
             ->addOption('--test', '-t', InputOption::VALUE_OPTIONAL, 'Tests filename');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dev = \bff::dev();
        $options = array(
            'suite' => 'unit',
        );

        $testName = $input->getOption('test');
        if (!empty($testName)) {
            $options['test'] = $testName;
        }

        if ($input->hasParameterOption(array('--extension', '-x'))) {
            $extension = $this->getExtensionByOption('extension', $input, $output);
            if ($extension === false) {
                return 1;
            }
            return $dev->testsExtension($extension, 'run', $options, true);
        } else {
            return $dev->testsCore('run', $options, true);
        }
    }
}