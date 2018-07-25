<?php namespace bff\console\commands;

/**
 * Консоль: команды режима обслуживания
 * @version 0.1
 * @modified 13.sep.2017
 */

use bff\console\commands\Command as Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Maintenance extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('maintenance')
             ->setDescription('Core maintenance')
             ->addOption('--action', '-a', InputOption::VALUE_REQUIRED, 'Action: "start", "stop", "migrate-status", "migrate"')
             ->addOption('--target', '-t', InputOption::VALUE_OPTIONAL, 'Migration version, "1.0.0"');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $action = $input->getOption('action');
        $params = array();

        switch ($action) {
            case 'migrate': {
                $params['target'] = $input->getOption('target');
            } break;
        }

        return \bff::dev()->maintenanceAction($action, $params, $input, $output);
    }
}