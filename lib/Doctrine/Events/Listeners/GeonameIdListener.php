<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Events\Listeners;

use Alchemy\Phrasea\Application;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Alchemy\Geonames\Exception\ExceptionInterface as GeonamesExceptionInterface;
use Entities\User;

class GeonameIdListener
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function preUpdate(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();

        if ($entity instanceof User && $event->hasChangedField('geonameId') && null !== $geonameId = $event->getNewValue('geonameId')) {
            try {
                $country = $this->app['geonames.connector']
                    ->geoname($geonameId)
                    ->get('country');

                if (isset($country['name'])) {
                    $entity->setNewValue('country', $country['name']);
                }
            } catch (GeonamesExceptionInterface $e) {

            }
        }
    }
}
