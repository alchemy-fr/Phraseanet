<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer\Utils;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Psr\Log\LoggerInterface;

class ComposerDriver extends AbstractBinary
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'composer';
    }

    /**
     * @param array|ConfigurationInterface $conf
     * @param LoggerInterface              $logger
     *
     * @return ComposerDriver
     */
    public static function create($conf = [], LoggerInterface $logger = null)
    {
        if (!$conf instanceof ConfigurationInterface) {
            $conf = new Configuration($conf);
        }

        $binaries = $conf->get('composer.binaries', ['composer']);

        return static::load($binaries, $logger, $conf);
    }
}
