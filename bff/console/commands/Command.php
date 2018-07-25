<?php namespace bff\console\commands;

/**
 * Консоль: абстракстный класс команды
 */

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
    }

    /**
     * @param string $optionName
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool|\Plugin|\Theme
     */
    protected function getExtensionByOption($optionName, InputInterface $input, OutputInterface $output)
    {
        $extension = false;

        do {
            $value = strval($input->getOption($optionName));
            $value = explode('/', $value, 2);
            if (empty($value[0]) || empty($value[1]) || !in_array($value[0], array('plugin', 'theme'))) {
                $output->writeln('<error>' . _t('dev', 'Не удалось определить тип расширения') . '</error>');
                break;
            }
            $name = $value[1];
            switch ($value[0]) {
                case 'plugin': {
                    $extension = \bff::plugin($name);
                    if ($extension === false) {
                        $output->writeln('<error>' . _t('dev', 'Не удалось найти требуемое расширение') . '</error>');
                    }
                } break;
                case 'theme': {
                    $extension = \bff::theme($name, false);
                    if ($extension === false) {
                        $output->writeln('<error>' . _t('dev', 'Не удалось найти требуемое расширение') . '</error>');
                    }
                } break;
            }
        } while(false);

        return $extension;
    }
}