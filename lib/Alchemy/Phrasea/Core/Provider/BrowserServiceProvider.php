<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class BrowserServiceProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        $app['browser'] = function ($app) {
            return new \Browser();
        };
    }
}
