<?php

namespace Alchemy\Phrasea\Command\Setup;

use Alchemy\Phrasea\Cache\Factory;
use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SystemClearSessionCacheCommand extends Command
{
    public function __construct()
    {
        parent::__construct('system:clear-session-cache');

        $this->setDescription('Empties session cache in redis, ends sessions for all users');

        return $this;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        /** @var Factory $cacheFactory */
        $cacheFactory = $this->container['phraseanet.cache-factory'];
        $cache = $cacheFactory->create('redis', ['host' => 'redis-session', 'port' => '6379']);

        $flushOK = $cache->removeByPattern('PHPREDIS_SESSION*');

        if ($flushOK) {
            $output->writeln('session cache in redis successfully flushed!');
        } else {
            $output->writeln('flush failed!');
        }

        return 0;
    }
}
