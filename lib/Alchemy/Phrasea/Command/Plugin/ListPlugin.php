<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Plugin;

use Alchemy\Phrasea\Plugin\Plugin;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListPlugin extends AbstractPluginCommand
{
    public function __construct()
    {
        parent::__construct('plugins:list');

        $this
            ->setDescription('Lists installed plugins');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $plugins = array_map(function (Plugin $plugin) {
            if ($plugin->isErroneous()) {
                return array('<error>'.$plugin->getName().'</error>', '<error>Error : '.$plugin->getError()->getMessage().'</error>', '');
            }

            return array($plugin->getName(), $plugin->getManifest()->getVersion(), $plugin->getManifest()->getDescription());
        }, $this->container['plugins.manager']->listPlugins());

        $table = $this->getHelperSet()->get('table');
        $table
            ->setHeaders(array('Name', 'Version', 'Description'))
            ->setRows($plugins)
        ;

        $table->render($output);

        return 0;
    }
}
