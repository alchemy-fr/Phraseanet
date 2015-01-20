<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer\Utils;

use Alchemy\BinaryDriver\AbstractBinary;
use Psr\Log\LoggerInterface;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;

class RecessDriver extends AbstractBinary
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'recess';
    }

    /**
     * @param array|ConfigurationInterface $conf
     * @param LoggerInterface              $logger
     *
     * @return RecessDriver
     */
    public static function create($conf = array(), LoggerInterface $logger = null)
    {
        if (!$conf instanceof ConfigurationInterface) {
            $conf = new Configuration($conf);
        }

        $binaries = $conf->get('recess.binaries', array('recess'));

        return static::load($binaries, $logger, $conf);
    }
}
