<?php namespace bff\console\commands\migrations;

/**
 * Консоль: команда отмены миграции БД ядра/расширения
 * @version 0.1
 * @modified 9.may.2018
 * Examples:
 * Core:
 *   php bffc migrations/rollback -t 1.0.0
 * Extensions:
 *   php bffc migrations/rollback -x plugin/name -t 1.0.0
 *   php bffc migrations/rollback -x theme/name -t 1.0.0
 */

use bff\console\commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Rollback extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('migrations/rollback')
             ->setDescription('Core/extension database rollback')
             ->addOption('--extension', '-x', InputOption::VALUE_OPTIONAL, 'Extension name: "plugin/name", "theme/name"')
             ->addOption('--target', '-t', InputOption::VALUE_OPTIONAL, 'Migration version: "1.0.0"');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dev = \bff::dev();

        if ($input->hasParameterOption(array('--extension', '-x'))) {
            $extension = $this->getExtensionByOption('extension', $input, $output);
            if ($extension === false) {
                return 1;
            }
            $target = $input->getOption('target');
            if (!empty($target)) {
                $target = $dev->migrationsExtension($extension, 'id', array('name'=>$target));
                if (empty($target)) {
                    $output->writeln('<error>'._t('dev','Не удалось найти требуемую версию миграции').'</error>');
                    return 1;
                }
            } else {
                $target = null;
            }
            return $dev->migrationsExtension($extension, 'rollback', array(
                'target' => $target,
            ), true);
        } else {
            $target = $input->getOption('target');
            if (!empty($target)) {
                $target = $dev->migrationsCore('id', array('name' => $target));
                if (empty($target)) {
                    $output->writeln('<error>' . _t('dev', 'Не удалось найти требуемую версию миграции') . '</error>');
                    return 1;
                }
            } else {
                $target = null;
            }

            return $dev->migrationsCore('rollback', array(
                'target' => $target,
            ), true);
        }
    }
}