<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Phrasea;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Collection\CollectionEvents;
use Alchemy\Phrasea\Core\Event\Collection\CreatedEvent;
use Alchemy\Phrasea\Core\Event\PostAuthenticate;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PhraseaEngineSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function onAuthenticate(PostAuthenticate $event)
    {
        $this->app['acl']->get($event->getUser())->inject_rights();
    }

    public function onCollectionCreate(CreatedEvent $event)
    {
        $sql = 'SELECT Users.id, c.session_id
                FROM (Users, Sessions s, basusr b)
                LEFT JOIN cache c ON (c.usr_id = Users.id)
                WHERE Users.model_of = 0 AND Users.deleted = 0
                  AND b.base_id = :base_id AND b.usr_id = Users.id AND b.actif=1
                  AND s.usr_id = Users.id';

        $stmt = $this->app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute([':base_id' => $event->getCollection()->get_base_id()]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $initialized = false;

        foreach ($rows as $row) {
            $user = $this->app['repo.users']->find($row['usr_id']);
            $this->app['acl']->get($user)->inject_rights();
            if (null !== $row['session_id']) {
                if (!$initialized) {
                    $this->app['phraseanet.SE']->initialize();
                    $initialized = true;
                }
                phrasea_clear_cache($row['session_id']);
                phrasea_close_session($row['session_id']);
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::POST_AUTHENTICATE => ['onAuthenticate', 0],
            CollectionEvents::CREATED => ['onCollectionCreate', 0],
        ];
    }
}
