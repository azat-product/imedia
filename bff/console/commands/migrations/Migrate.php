<?php namespace bff\console\commands\migrations;

/**
 * Консоль: команда миграции БД ядра/расширения
 * @version 0.1
 * @modified 9.may.2018
 * Examples:
 * Core:
 *   php bffc migrations/migrate -t 2.4.0
 * Extensions:
 *   php bffc migrations/migrate -x plugin/name -t 2.4.0
 *   php bffc migrations/migrate -x theme/name -t 2.4.0
 */

use bff\console\commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Migrate extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('migrations/migrate')
             ->setDescription('Core/extension database migration')
             ->addOption('--extension', '-x', InputOption::VALUE_OPTIONAL, 'Extension name: "plugin/name", "theme/name"')
             ->addOption('--target', '-t', InputOption::VALUE_OPTIONAL, 'Migration version, "1.0.0"');
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
            }
            return $dev->migrationsExtension($extension, 'migrate', array(
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
            }
            return $dev->migrationsCore('migrate', array(
                'target' => $target,
            ), true);
        }
    }
}