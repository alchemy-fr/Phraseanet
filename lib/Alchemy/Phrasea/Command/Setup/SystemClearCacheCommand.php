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
use Symfony\Component\Finder\Finder;

class SystemClearCacheCommand extends Command
{

    public function __construct()
    {
        parent::__construct('system:clear-cache');

        $this->setDescription('Empties cache directories and cache-server data');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $finder = new Finder();

        $in = $this->container['cache.paths']->getArrayCopy();
        $finder
            ->exclude('.git')
            ->exclude('.svn')
            ->in($in);

        $this->container['filesystem']->remove($finder);

        if ($this->container['phraseanet.configuration-tester']->isInstalled()) {
            $this->getService('phraseanet.cache-service')->flushAll();
        }

        $output->write('Finished !', true);

        return 0;
    }
}
