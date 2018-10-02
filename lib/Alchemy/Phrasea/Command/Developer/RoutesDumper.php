<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RoutesDumper extends AbstractRoutesDumper
{
    public function __construct()
    {
        parent::__construct('routes:dump');

        $this->setDescription('Dumps Phraseanet routes');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        return $this->dumpRoutes($this->container['routes'], $input, $output);
    }
}
