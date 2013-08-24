<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Doctrine\Events\Suscribers\UserSuscriber;

use Alchemy\Geonames\Connector as GeonamesConnector;
use Alchemy\Geonames\Exception\ExceptionInterface as GeonamesExceptionInterface;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\Common\Persistence\Event\PreUpdateEventArgs;
use Entities\User;

class UserSubscriber implements EventSubscriber
{
    /**
     * @var GeonamesConnector
     */
    private $geonamesConnector;

    public function __construct(GeonamesConnector $connector)
    {
        $this->geonamesConnector = $connector;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::preUpdate,
            Events::preRemove
        );
    }

    /**
     * Handles preUpdate event stuff
     *
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event)
    {
        $entity = $event->getObject();

        if ($entity instanceof User && $event->hasChangedField('modelOf')) {
            $event->setNewValue('city', '');
            $event->setNewValue('address', '');
            $event->setNewValue('country', '');
            $event->setNewValue('zipCode', '');
            $event->setNewValue('timezone', '');
            $event->setNewValue('company', '');
            $event->setNewValue('email', null);
            $event->setNewValue('fax', '');
            $event->setNewValue('phone', '');
            $event->setNewValue('firstName', '');
            $event->setNewValue('gender', null);
            $event->setNewValue('geonameId', null);
            $event->setNewValue('job', '');
            $event->setNewValue('activity', '');
            $event->setNewValue('lastName', '');
            $event->setNewValue('$mailNotificationsActivated', false);
            $event->setNewValue('$requestNotificationsActivated', false);

            // @todo @nlegoff reset user_settings, user_notif_settings, ftp_credentials
            // according to Doctrine doc :
            //
            // Changes to associations of the updated entity are never allowed in this event,
            // since Doctrine cannot guarantee to correctly handle referential integrity
            // at this point of the flush operation.
            //
            // Move the logic up to the service layer by using an entity manager.
            // Write a specific method for updating template entity and do all the complex stuff there.
            // Like FOSUserBundle's UserManager
            // https://github.com/FriendsOfSymfony/FOSUserBundle/blob/v1.3.1/Doctrine/UserManager.php
        }

        if ($entity instanceof User
                && $event->hasChangedField('geonameId')
                && null !== $geonameId = $event->getNewValue('geonameId')) {
            try {
                $country = $this->geonamesConnector
                    ->geoname($geonameId)
                    ->get('country');

                if (isset($country['name'])) {
                    $event->setNewValue('country', $country['name']);
                }
            } catch (GeonamesExceptionInterface $e) {

            }
        }
    }

    /**
     * Handles preRemove event stuff
     *
     * @param LifecycleEventArgs $event
     */
    public function preRemove(LifecycleEventArgs $event)
    {
        $entity = $event->getObject();
        $em = $event->getObjectManager();

        if ($entity instanceof User) {
            // Set user as a deleted user @todo @nlegoff set deleted boolean
            $entity->setDeleted(true);
            $entity->setEmail(null);
            $entity->setLogin(sprintf('(#deleted_%s', $entity->getLogin()));

            $this->clean($entity);

             // Cancel deletion by persisting entity again
            $em->persist($entity);
        }
    }

    private function clean(User $user)
    {
        $conn = $this->app['phraseanet.appbox']->get_connection();
        foreach(array(
            'basusr',
            'sbasusr',
            'edit_presets',
            'ftp_export',
            'order',
            'sselnew',
            'tokens',
        ) as $table) {
            $stmt = $conn->prepare('DELETE FROM ' .$table. ' WHERE usr_id = :usr_id');
            $stmt->execute(array(':usr_id' => $user->getId()));
            $stmt->closeCursor();
        }
        unset($stmt);

        $this->cleanRelations($user);
    }

    private function cleanRelations(User $user)
    {
        foreach($user->getNotificationSettings() as $userNotificatonSetting) {
            $userNotificatonSetting->setUser(null);
        }

        $user->getNotificationSettings()->clear();

        foreach($user->getSettings() as $userSetting) {
            $userSetting->setUser(null);
        }

        $user->getSettings()->clear();

        foreach($user->getQueries() as $userQuery) {
            $userQuery->setUser(null);
        }

        $user->getQueries()->clear();
    }
}
