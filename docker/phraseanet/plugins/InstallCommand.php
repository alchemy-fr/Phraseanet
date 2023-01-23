<?php

namespace Alchemy\Docker\Plugins\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('install')
            ->setDescription('Install plugins');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $plugins = trim(getenv('PHRASEANET_PLUGINS'));
        if (empty($plugins)) {
            $output->writeln('<comment>No plugin to install... SKIP</comment>');

            return 0;
        }

        $pluginsDir = 'plugins';
        if (!is_dir($pluginsDir)) {
            mkdir($pluginsDir);
        }

        foreach (explode(';', $plugins) as $key => $plugin) {
            $plugin = trim($plugin);
            $repo = $plugin;
            $branch = 'master';
            if (1 === preg_match('#^(.+)\(([^)]+)\)$#', $plugin, $matches)) {
                $repo = $matches[1];
                $branch = $matches[2];
            }

            $pluginPath = './plugin' . $key;
            if (is_dir($pluginPath)) {
                SubCommand::run(sprintf('rm -rf %s', $pluginPath));
            }

            $output->writeln(sprintf('Installing <info>%s</info> (branch: <info>%s</info>)', $repo, $branch));
            SubCommand::run(sprintf('git clone --single-branch --branch %s %s %s', $branch, $repo, $pluginPath));

            $manifestSrc = $pluginPath.'/manifest.json';
            if (!file_exists($manifestSrc)) {
                throw new \Exception(sprintf('Cannot install plugin %s: no manifest.json file found', $plugin));
            }
            $pluginDestName = json_decode(file_get_contents($manifestSrc), true)['name'];
            rename($pluginPath, $pluginsDir.'/'.$pluginDestName);
            $pluginPath = $pluginsDir.'/'.$pluginDestName;

            if (file_exists($pluginPath.'/composer.json')) {
                SubCommand::run(sprintf('cd %s && composer install --no-dev', $pluginPath));
            }
        }

        return 0;
    }
}
