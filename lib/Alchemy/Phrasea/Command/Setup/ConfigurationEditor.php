<?php

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Core\Configuration\Configuration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ConfigurationEditor extends Command
{
    private $noCompile = false;
    private $quiet = false;

    public function __construct($name)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->addArgument(
            'operation',
            InputArgument::REQUIRED,
            'The operation to execute (get, set, add, compile)'
        );

        $this->addArgument(
            'parameter',
            InputArgument::OPTIONAL,
            'The name of the configuration parameter to get or set'
        );

        $this->addArgument(
            'value',
            InputArgument::OPTIONAL,
            'The value to set when operation is "set" or "add", in YAML syntax'
        );

        $this->addOption('no-compile', "s", InputOption::VALUE_NONE, 'Do not compile the config after save in yml file');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getArgument('operation');
        $parameter = $input->getArgument('parameter');
        $this->noCompile = $input->getOption('no-compile');
        $this->quiet = $input->getOption('quiet');

        $parameterNodes = explode('.', $parameter);

        if ($command == 'compile') {
            $this->compileConfiguration($output);
        } else {
            if (empty($parameter)) {
                $output->writeln("<error>Missing 'parameter' argument</error>");

                return 1;
            }

            if ($command == 'get') {
                $this->readConfigurationValue($output, $parameter, $parameterNodes);
            }
            elseif ($command == 'set') {
                $this->writeConfigurationValue($output, $parameter, $parameterNodes, $input->getArgument('value'));
            }
            elseif ($command == 'add') {
                $this->appendConfigurationValue($output, $parameter, $parameterNodes, $input->getArgument('value'));
            } else {
                $output->writeln("<error>Command not found</error>");
            }
        }
    }

    private function readConfigurationValue(OutputInterface $output, $parameter, array $parameterNodes)
    {
        $app = $this->getContainer();
        /** @var Configuration $config */
        $config = $app['configuration.store'];
        $config->setNoCompile($this->noCompile);
        $values = $config->getConfig();
        $current = $values;

        foreach ($parameterNodes as $paramName) {
            $current = $current[$paramName];
        }

        if (!$this->quiet) {
            $output->writeln('<info>Getting configuration entry</info> ' . $parameter);
        }

        $this->printConfigurationValue($output, $parameter, $current);
    }

    private function writeConfigurationValue(OutputInterface $output, $parameter, array $parameterNodes, $value)
    {
        $app = $this->getContainer();
        /** @var Configuration $configurationStore */
        $configurationStore = $app['configuration.store'];
        $configurationStore->setNoCompile($this->noCompile);
        $lastParameter = end($parameterNodes);

        $configurationRoot = $configurationStore->getConfig();
        $configurationCurrent = & $configurationRoot;

        if (!$this->quiet) {
            $output->writeln('<info>Writing value to configuration entry</info> ' . $parameter);
        }

        foreach ($parameterNodes as $paramName) {
            if (! isset($configurationCurrent[$paramName])) {
                $configurationCurrent[$paramName] = array();
            }

            if ($lastParameter == $paramName) {
                // if value is a file path, do not parse it
                $configurationCurrent[$paramName] = is_file($value) ? $value : Yaml::parse($value);
            }
            else {
                $configurationCurrent = & $configurationCurrent[$paramName];
            }
        }

        $configurationStore->setConfig($configurationRoot);
        if (!$this->noCompile) {
            $configurationStore->compileAndWrite();
        }

        if (!$this->quiet) {
            $output->writeln('<comment>Reading updated configuration value</comment>');
        }

        $this->readConfigurationValue($output, $parameter, $parameterNodes);
    }

    private function appendConfigurationValue(OutputInterface $output, $parameter, array $parameterNodes, $value)
    {
        $app = $this->getContainer();
        /** @var Configuration $configurationStore */
        $configurationStore = $app['configuration.store'];
        $configurationStore->setNoCompile($this->noCompile);
        $lastParameter = end($parameterNodes);

        $configurationRoot = $configurationStore->getConfig();
        $configurationCurrent = & $configurationRoot;

        if (!$this->quiet) {
            $output->writeln('<info>Appending value to configuration entry</info> ' . $parameter);
        }

        foreach ($parameterNodes as $paramName) {
            if (! isset($configurationCurrent[$paramName])) {
                $configurationCurrent[$paramName] = array();
            }

            if ($lastParameter == $paramName) {
                if (! is_array($configurationCurrent[$paramName])) {
                    $configurationCurrent[$paramName] = array($configurationCurrent[$paramName]);
                }

                $parsedValue = Yaml::parse($value);

                if (! is_array($parsedValue)) {
                    $parsedValue = [ $parsedValue ];
                }

                $configurationCurrent[$paramName] = array_merge($configurationCurrent[$paramName], $parsedValue);
                $configurationCurrent[$paramName] = array_unique($configurationCurrent[$paramName]);
            }
            else {
                $configurationCurrent = & $configurationCurrent[$paramName];
            }
        }

        $configurationStore->setConfig($configurationRoot);
        if (!$this->noCompile) {
            $configurationStore->compileAndWrite();
        }

        if (!$this->quiet) {
            $output->writeln('<comment>Reading updated configuration value</comment>');
        }

        $this->readConfigurationValue($output, $parameter, $parameterNodes);
    }

    private function printConfigurationValue(OutputInterface $output, $name, $value, $indent = 0)
    {
        if ($indent > 0 && !$this->quiet) {
            $output->write(PHP_EOL);
        }

        if (!$this->quiet) {
            $output->write(str_repeat(' ', $indent * 4) . (is_numeric($name) ? '- ' : $name . ': '));
        }

        if (is_array($value)) {
            if (empty($value) && !$this->quiet) {
                $output->write('[]');
            }

            foreach ($value as $valueName => $valueItem) {
                $this->printConfigurationValue($output, $valueName, $valueItem, $indent + 1);
            }
        }
        else {
            if (!$this->quiet) {
                $output->write(var_export($value));
            }
        }

        if ($indent == 0) {
            $output->write(PHP_EOL);
        }
    }

    private function compileConfiguration(OutputInterface $output)
    {
        $this->container['configuration.store']->compileAndWrite();
        $output->writeln('<comment>Configuration compiled!</comment>');
    }
}
