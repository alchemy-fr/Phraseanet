<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Plugin\Command;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Plugin\PluginException;
use Alchemy\Phrasea\Plugin\PluginRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginCommand extends Command
{
    private static $commandNamespace = 'plugins';
    private static $knownCommands = [
        'enable', 'disable', 'list',
    ];
    private $pluginCommandName;

    public function __construct($name = null)
    {
        $name = $name ?: 'list';

        if (!in_array($name, self::$knownCommands)) {
            throw new \UnexpectedValueException(sprintf('Expects $name to be one of (%s), got %s', implode(', ', self::$knownCommands), $name));
        }
        $this->pluginCommandName = $name;
        parent::__construct(self::$commandNamespace . ':' . $name);

        $method = 'set' . ucfirst($name) . 'Description';

        $this->{$method}();
    }

    public function setEnableDescription()
    {
        $this
            ->setDescription('Enables a plugin')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the plugin')
        ;
    }

    public function setDisableDescription()
    {
        $this
            ->setDescription('Disables a plugin')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the plugin')
        ;
    }

    public function setListDescription()
    {
        $this
            ->setDescription('List available plugins')
            ->addOption('json', 'j', InputOption::VALUE_NONE, 'Output result in JSON')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (in_array($this->pluginCommandName, ['enable', 'disable'])) {
            return $this->changePluginState($input, $output);
        }

        return $this->listPlugins($input, $output);
    }

    private function changePluginState(InputInterface $input, OutputInterface $output)
    {
        /** @var PluginRepository $repository */
        $repository = $this->getService('plugins.repository');
        $name = $input->getArgument('name');

        try {
            $repository->find($name);
        } catch (PluginException $exception) {
            $output->writeln(sprintf('There is no plugin named <comment>%s</comment>, aborting', $name));

            return 1;
        }

        $pluginManager = $this->getContainer()->getPluginManager();
        if ('enable' == $this->pluginCommandName) {
            $configurationChanged = $pluginManager->enablePlugin($name);
        } else {
            $configurationChanged = $pluginManager->disablePlugin($name);
        }

        $output->writeln(sprintf(
            'Plugin named <info>%s</info> %s %s',
            $name,
            $configurationChanged ? 'is now' : 'was already',
            $this->pluginCommandName . 'd'
        ));

        return 0;
    }

    private function listPlugins(InputInterface $input, OutputInterface $output)
    {
        /** @var PluginRepository $repository */
        $repository = $this->getService('plugins.repository');

        $plugins = [];
        foreach ($repository->findAll() as $name => $data) {
            $plugins[$name] = ['name' => $data['name'], 'basePath' => $data['basePath']];
        }
        if ($input->getOption('json')) {
            $output->writeln(json_encode(['plugins' => $plugins]));

            return 0;
        }

        return 0;
    }
}
