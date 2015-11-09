<?php

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event;

class CollectionSettingsChanged extends CollectionRelated
{
    public function getSettingsBefore()
    {
        return $this->args['settings_before'];
    }
}
