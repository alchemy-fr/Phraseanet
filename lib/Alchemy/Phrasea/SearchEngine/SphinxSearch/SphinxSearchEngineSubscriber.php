<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Phrasea;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SphinxSearchEngineSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array();
    }
}
