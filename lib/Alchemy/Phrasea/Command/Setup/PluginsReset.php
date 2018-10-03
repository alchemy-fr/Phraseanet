<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginsReset extends Command
{
    public function __construct()
    {
        parent::__construct('plugins:reset');

        $this->setDescription('Reset plugins in case a failure occured');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $this->container['filesystem']->remove($this->container['plugin.path']);
        $this->container['filesystem']->mirror(__DIR__ . '/../../../../conf.d/plugins', $this->container['plugin.path']);

        return 0;
    }
}
