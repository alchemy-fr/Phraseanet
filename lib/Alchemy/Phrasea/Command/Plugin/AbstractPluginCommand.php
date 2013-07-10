<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractPluginCommand extends Command
{
    protected function validatePlugins(InputInterface $input, OutputInterface $output)
    {
        $manifests = array();

        $output->write("Validating plugins...");
        foreach ($this->container['plugins.explorer'] as $directory) {
            $manifests[] = $manifest = $this->container['plugins.plugins-validator']->validatePlugin($directory);
        }
        $output->writeln(" <comment>OK</comment>");

        return $manifests;
    }

    protected function updateConfigFiles(InputInterface $input, OutputInterface $output)
    {
        $manifests = $this->validatePlugins($input, $output);

        $output->write("Updating config files...");
        $this->container['plugins.autoloader-generator']->write($manifests);
        $output->writeln(" <comment>OK</comment>");

        $files = array(
            $this->container['root.path'] . '/www/skins/login/less/login.less' => $this->container['root.path'] . '/www/skins/build/login.css',
            $this->container['root.path'] . '/www/skins/account/account.less' => $this->container['root.path'] . '/www/skins/build/account.css',
        );

        $output->write('Building Assets...');
        $errors = $this->container['phraseanet.less-builder']->build($files);

        if (count($errors) > 0) {
            $output->writeln(sprintf('<error>Error(s) occured during the build %s</error>', implode(', ', $errors)));
        }
        $output->writeln(" <comment>OK</comment>");
    }
}
