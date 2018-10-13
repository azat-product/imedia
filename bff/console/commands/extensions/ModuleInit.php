<?php namespace bff\console\commands\extensions;

/**
 * Консоль: команда инициализации модуля расширения
 * @version 0.2
 * @modified 31.aug.2018
 * Examples:
 *   php bffc extensions/moduleInit -x plugin/name --name=moduleName
 *   php bffc extensions/moduleInit -x theme/name --name=moduleName
 */

use bff\console\commands\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ModuleInit extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('extensions/moduleInit')
             ->setDescription('Extension module init')
             ->addOption('--extension', '-x', InputOption::VALUE_REQUIRED, 'Extension name: "plugin/name", "theme/name"')
             ->addOption('--name', null, InputOption::VALUE_REQUIRED, 'Module registration name (a_z_): "example"')
             ->addOption('--class', null, InputOption::VALUE_OPTIONAL, 'Module class name (a_z_): "example"')
             ->addOption('--path', null, InputOption::VALUE_OPTIONAL, 'Module path, default "" (in extension directory)')
            ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return mixed
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $extension = $this->getExtensionByOption('extension', $input, $output);
        if ($extension === false) {
            return 1;
        }

        $namespace = '';
        $namespaceSeparator = '\\';
        if ($extension->isPlugin()) {
            $alias = trim($extension->getAlias(), $namespaceSeparator);
            if ( ! empty($alias)) {
                $namespace = $extension::dir($extension->getExtensionType()) . $namespaceSeparator . $alias;
            }
        }
        $name = $input->getOption('name');
        $name = trim(preg_replace('/[^a-z\-\_]/u', '', mb_strtolower($name)));
        $name = trim(str_replace('-', '_', $name), '_');

        $className = $input->getOption('class');
        $className = trim(preg_replace('/[^a-zA-Z\_]/u', '', $className));
        if (empty($className)) {
            $className = $name;
        }

        $path = $input->getOption('path');
        $path = trim(trim(trim($path), DIRECTORY_SEPARATOR));
        if (empty($path)) {
            $path = '';
        } else if ( ! empty($namespace)) {
            $pathNS = trim(str_replace(DIRECTORY_SEPARATOR, $namespaceSeparator, $path), '\\');
            if ( ! empty($pathNS)) {
                $namespace .= $namespaceSeparator . $pathNS;
            }
        }

        $success = \bff::dev()->createModule($name, $name, array(
            'path' => $extension->path($path),
            'plugin' => (!empty($alias) ? $alias : false),
            'className' => $className,
            'namespace' => $namespace,
        ));
        if ( ! $success) {
            foreach (\bff::errors()->get(true, false) as $error) {
                $output->writeln('<error>' . $error . '</error>');
            }
            return 1;
        } else {
            $output->writeln('<info>' . _t('dev', 'Модуль был успешно создан') . '</info>');
            $opts = '';
            if ( ! empty($namespace)) {
                $opts = ', [\'class\'=>\''.$namespace.'\\'.$className.'\']';
            }
            $output->writeln('<question>' . _t('dev', 'Код регистрации: $this->moduleRegister([options]);', array(
                'options' => "'{$name}', '{$path}'{$opts}",
            )) . '</question>');
        }
    }
}