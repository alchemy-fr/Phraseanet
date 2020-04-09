<?php


namespace Alchemy\DevToolsPlugin\EventsLogger;


class EventsSources
{
    private static $sources = false;
    private static $_classes = [
        'Alchemy\Phrasea\Core\PhraseaEvents',
        'Alchemy\Phrasea\Core\Event\Acl\AclEvents',
        'Alchemy\Phrasea\Core\Event\Collection\CollectionEvents',
        'Alchemy\Phrasea\Core\Event\Databox\DataboxEvents',
        'Alchemy\Phrasea\Core\Event\Record\RecordEvents',
        'Alchemy\Phrasea\Core\Event\Record\Structure\RecordStructureEvents',
        'Alchemy\Phrasea\Core\Event\User\UserEvents',
    ];

    public static function getSources()
    {
        if(!self::$sources) {
            self::$sources = [];
            foreach (self::$_classes as $_class) {
                $a = explode('\\', $_class);
                $k = array_pop($a);
                $c = [];
                try {
                    $r = new \ReflectionClass($_class);
                    foreach ($r->getConstants() as $constant) {
                        $c[] = $constant;
                    }
                    self::$sources[$k] = [
                        'class' => $_class,
                        'constants' => $c
                    ];
                }
                catch (\Exception $e) {
                    // no-op;
                }
            }
        }

        return self::$sources;
    }

}