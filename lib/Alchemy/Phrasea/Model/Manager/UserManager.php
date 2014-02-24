<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UserSetting;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\UnitOfWork AS UOW;

class UserManager
{
    /** @var ObjectManager */
    protected $objectManager;
    /** @var \PDO */
    protected $appboxConnection;

    public function __construct(ObjectManager $om, Connection $appboxConnection)
    {
        $this->objectManager = $om;
        $this->appboxConnection = $appboxConnection;
    }

    /**
     * Creates a new user.
     *
     * @return User
     */
    public function create()
    {
        return new User();
    }

    /**
     * Deletes an user.
     *
     * @param User $user
     * @param type $flush
     */
    public function delete(User $user, $flush = true)
    {
        $this->cleanProperties($user);
        $this->cleanRights($user);

        $this->objectManager->persist($user);
        if ($flush) {
            $this->objectManager->flush();
        }
    }

    /**
     * Updates an user.
     *
     * @param User $user
     * @param type $flush
     */
    public function update(User $user, $flush = true)
    {
        $this->objectManager->persist($user);
        if ($flush) {
            $this->objectManager->flush();
        }
    }

    /**
     * Gets the object manager.
     *
     * @return ObjectManager
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Removes user settings.
     *
     * @param User $user
     */
    private function cleanSettings(User $user)
    {
        foreach ($user->getNotificationSettings() as $userNotificationSetting) {
            $this->objectManager->remove($userNotificationSetting);
        }

        $user->getNotificationSettings()->clear();

        foreach ($user->getSettings() as $userSetting) {
            $this->objectManager->remove($userSetting);
        }

        $user->getSettings()->clear();
    }

    /**
     * Removes user queries.
     *
     * @param User $user
     */
    private function cleanQueries(User $user)
    {
        foreach ($user->getQueries() as $userQuery) {
            $this->objectManager->remove($userQuery);
        }

        $user->getQueries()->clear();
    }

    /**
     * Removes user ftp credentials.
     *
     * @param User $user
     */
    private function cleanFtpCredentials(User $user)
    {
        if (null !== $credential = $user->getFtpCredential()) {
            $this->objectManager->remove($credential);
        }
    }

    /**
     * Removes user ftp export.
     *
     * @param User $user
     */
    private function cleanFtpExports(User $user)
    {
       $elements = $this->objectManager->getRepository('Phraseanet:FtpExport')
               ->findBy(['user' => $user]);

       foreach ($elements as $element) {
           $this->objectManager->remove($element);
       }
    }

    /**
     * Removes user orders.
     *
     * @param User $user
     */
    private function cleanOrders(User $user)
    {
       $orders = $this->objectManager->getRepository('Phraseanet:Order')
               ->findBy(['user' => $user]);

       foreach ($orders as $order) {
           $this->objectManager->remove($order);
       }
    }

    /**
     * Removes user orders.
     *
     * @param User $user
     */
    private function cleanUserSessions(User $user)
    {
        $sessions = $this->objectManager->getRepository('Phraseanet:Session')
            ->findByUser(['user' => $user]);

        foreach ($sessions as $session) {
            $this->objectManager->remove($session);
        }
    }

    /**
     * Removes user providers.
     *
     * @param User $user
     */
    private function cleanAuthProvider(User $user)
    {
        $providers = $this->objectManager->getRepository('Phraseanet:UsrAuthProvider')
            ->findBy(['user' => $user]);

        foreach ($providers as $provider) {
            $this->objectManager->remove($provider);
        }
    }

    /**
     * Removes all user's properties.
     *
     * @param User $user
     */
    private function cleanProperties(User $user)
    {
        foreach ([
            'DELETE FROM `edit_presets` WHERE usr_id = :usr_id',
            'DELETE FROM `tokens` WHERE usr_id = :usr_id',
        ] as $sql) {
            $stmt = $this->appboxConnection->prepare($sql);
            $stmt->execute([':usr_id' => $user->getId()]);
            $stmt->closeCursor();
        }

        $this->cleanSettings($user);
        $this->cleanQueries($user);
        $this->cleanFtpCredentials($user);
        $this->cleanOrders($user);
        $this->cleanFtpExports($user);
        $this->cleanAuthProvider($user);
        $this->cleanUserSessions($user);
    }

    /**
     * Removes all user's rights.
     *
     * @param User $user
     */
    private function cleanRights(User $user)
    {
        foreach ([
            'DELETE FROM `basusr` WHERE usr_id = :usr_id',
            'DELETE FROM `sbasusr` WHERE usr_id = :usr_id',
        ] as $sql) {
            $stmt = $this->appboxConnection->prepare($sql);
            $stmt->execute([':usr_id' => $user->getId()]);
            $stmt->closeCursor();
        }
    }
}
