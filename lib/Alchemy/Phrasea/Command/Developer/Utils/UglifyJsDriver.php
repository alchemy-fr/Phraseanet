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

class UglifyJsDriver extends AbstractBinary
{
    public function getName()
    {
        return 'uglifyjs';
    }

    public static function create($conf = array(), LoggerInterface $logger = null)
    {
        if (!$conf instanceof ConfigurationInterface) {
            $conf = new Configuration($conf);
        }

        $binaries = $conf->get('uglifyjs.binaries', array('uglifyjs'));

        return static::load($binaries, $logger, $conf);
    }
}
