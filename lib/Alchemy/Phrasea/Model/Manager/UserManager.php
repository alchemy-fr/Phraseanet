<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manager;

use Doctrine\Common\Persistence\ObjectManager;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UserSetting;

class UserManager
{
    /** @var ObjectManager */
    protected $objectManager;
    /** @var \PDO */
    protected $appboxConnection;

    public function __construct(ObjectManager $om, \PDO $appboxConnection)
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
        $user->setDeleted(true);
        $user->setEmail(null);
        $user->setLogin(sprintf('(#deleted_%s', $user->getLogin()));

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
       $elements = $this->objectManager->getRepository('Alchemy\Phrasea\Model\Entities\FtpExport')
               ->findBy(['usrId' => $user->getId()]);

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
       $orders = $this->objectManager->getRepository('Alchemy\Phrasea\Model\Entities\Order')
               ->findBy(['usrId' => $user->getId()]);

       foreach ($orders as $order) {
           $this->objectManager->remove($order);
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
