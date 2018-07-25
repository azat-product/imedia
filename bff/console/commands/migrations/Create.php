<?php namespace bff\console\commands\migrations;

/**
 * Консоль: команда создания миграции БД ядра/расширения
 * @version 0.11
 * @modified 6.feb.2018
 */

use bff\console\commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('migrations/create')
             ->setDescription('Core/extension database migration create')
             ->addOption('--extension', '-x', InputOption::VALUE_OPTIONAL, 'Extension name: "plugin/name", "theme/name"')
             ->addOption('--target', '-t', InputOption::VALUE_REQUIRED, 'Migration version name, "1.0.0"');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dev = \bff::dev();
        $versionValidate = function(&$version) {
            $version = trim(preg_replace('/[^0-9\.]/', '', $version), '.');
            if ( ! \bff::input()->isVersionNumber($version)) {
                return false;
            }
            return true;
        };

        if ($input->hasParameterOption(array('--extension', '-x'))) {
            $extension = $this->getExtensionByOption('extension', $input, $output);
            if ($extension === false) {
                return 1;
            }
            $version = $input->getOption('target');
            if ( ! $versionValidate($version)) {
                $output->writeln('<error>'._t('dev', 'Название версии указано некорректно').'</error>');
                return 1;
            }
            $versionExists = $dev->migrationsExtension($extension, 'id', array('name'=>$version));
            if (!empty($versionExists)) {
                $output->writeln('<error>'._t('dev', 'Миграция с таким названием версии ([version]) уже существует', array('version' => $version)).'</error>');
                return 1;
            }
            return $dev->migrationsExtension($extension, 'create', array(
                'version' => $version,
            ), true);
        } else {
            $version = $input->getOption('target');
            if ( ! $versionValidate($version)) {
                $output->writeln('<error>'._t('dev', 'Название версии указано некорректно').'</error>');
                return 1;
            }
            $versionExists = $dev->migrationsCore('id', array('name' => $version));
            if (!empty($versionExists)) {
                $output->writeln('<error>'._t('dev', 'Миграция с таким названием версии ([version]) уже существует', array('version' => $version)).'</error>');
                return 1;
            }
            return $dev->migrationsCore('create', array(
                'version' => $version,
            ), true);
        }
    }
}