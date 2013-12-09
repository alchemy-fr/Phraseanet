<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer\Utils;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Psr\Log\LoggerInterface;

class BowerDriver extends AbstractBinary
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bower';
    }

    /**
     * @param array|ConfigurationInterface $conf
     * @param LoggerInterface              $logger
     *
     * @return BowerDriver
     */
    public static function create($conf = [], LoggerInterface $logger = null)
    {
        if (!$conf instanceof ConfigurationInterface) {
            $conf = new Configuration($conf);
        }

        $binaries = $conf->get('bower.binaries', ['bower']);

        return static::load($binaries, $logger, $conf);
    }
}
