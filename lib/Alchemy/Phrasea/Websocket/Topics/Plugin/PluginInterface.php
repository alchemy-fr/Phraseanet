<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Websocket\Topics\Plugin;

use Alchemy\Phrasea\Websocket\Topics\TopicsManager;

interface PluginInterface
{
    /**
     * Attaches a Plugin to the TopicsManager
     *
     * @param TopicsManager $manager
     */
    public function attach(TopicsManager $manager);
}
