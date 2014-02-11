<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command;

use Alchemy\Phrasea\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebsocketServer extends Command
{
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this
            ->setDescription("Runs the websocket server");
    }

    public function doExecute(InputInterface $input, OutputInterface $output)
    {
        $sessionConf = $this->container['conf']->get(['main', 'session', 'type'], 'file');

        if (!in_array($sessionConf, ['memcached', 'memcache', 'redis'])) {
            throw new RuntimeException(sprintf('Running the websocket server requires a server session storage, type `%s` provided', $sessionConf));
        }

        $this->container['ws.server']->run();
    }
}
