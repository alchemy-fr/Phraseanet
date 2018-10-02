<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin;

use Alchemy\Phrasea\Application;
use Silex\ServiceProviderInterface;

interface PluginProviderInterface extends ServiceProviderInterface
{
    /**
     * Factory for the plugin.
     *
     * This method is called to build it.
     *
     * @param Application $app
     *
     * @return PluginProviderInterface
     */
    public static function create(Application $app);
}
