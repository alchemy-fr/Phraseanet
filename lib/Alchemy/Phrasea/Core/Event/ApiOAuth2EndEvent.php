<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class ApiOAuth2EndEvent extends SfEvent
{
    public function getName()
    {
        return PhraseaEvents::API_OAUTH2_END;
    }
}
