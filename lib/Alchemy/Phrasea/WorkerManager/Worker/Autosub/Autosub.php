<?php

namespace Alchemy\Phrasea\WorkerManager\Worker\Autosub;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException as BinaryDriverExecutableNotFound;
use FFMpeg\Exception\ExecutableNotFoundException;
use Psr\Log\LoggerInterface;

class Autosub extends AbstractBinary
{

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return 'autosub';
    }

    public static function create(LoggerInterface $logger = null, $configuration = array())
    {
        if (!$configuration instanceof ConfigurationInterface) {
            $configuration = new Configuration($configuration);
        }

        $binaries = ['autosub'];

        try {
            return static::load($binaries, $logger, $configuration);
        } catch (BinaryDriverExecutableNotFound $e) {
            throw new ExecutableNotFoundException('Unable to load autosub', $e->getCode(), $e);
        }
    }
}
