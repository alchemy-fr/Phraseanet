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

use Alchemy\Phrasea\Model\Entities\ApiLog;
use Alchemy\Phrasea\Model\Entities\UsrList;
use Alchemy\Phrasea\Model\Entities\UsrListOwner;
use Doctrine\Common\Persistence\ObjectManager;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UserSetting;
use Doctrine\DBAL\Driver\Connection;
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
     * @param bool $flush
     */
    public function delete(User $user, $flush = true)
    {
        $this->cleanProperties($user);
        $this->cleanRights($user);
        $this->cleanNotifications($user);

        $this->objectManager->persist($user);

        if ($flush) {
            $this->objectManager->flush();
            $this->objectManager->clear(ApiLog::class);
        }
    }

    /**
     * Updates an user.
     *
     * @param User $user
     * @param bool $flush
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

    private function cleanNotifications(User $user)
    {
        $sql = 'DELETE FROM notifications WHERE usr_id = :usr_id';
        $stmt = $this->appboxConnection->prepare($sql);
        $stmt->execute([':usr_id' => $user->getId()]);
        $stmt->closeCursor();
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

    private function cleanTokens(User $user)
    {
        $elements = $this->objectManager->getRepository('Phraseanet:Token')
            ->findBy(['user' => $user]);

        foreach ($elements as $element) {
            $this->objectManager->remove($element);
        }
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
            $user->setFtpCredential(null);
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
     * Removes user list.
     *
     * @param User $user
     */
    private function cleanUsrList(User $user)
    {
        $listOwners = $this->objectManager->getRepository('Phraseanet:UsrListOwner')
            ->findBy(['user' => $user]);

        /** @var UsrListOwner $listOwner */
        foreach ($listOwners as $listOwner) {
            $usrList = $listOwner->getList();
            $listOwnersAdmin = $this->objectManager->getRepository('Phraseanet:UsrListOwner')
                ->findBy(['list' => $usrList, 'role' => '3']);

            // there are only one administrator owner and it is the user
            if (count($listOwnersAdmin) == 1 &&  $listOwnersAdmin[0]->getUser()->getId() === $user->getId()) {
                $this->objectManager->remove($usrList);
            }

            $this->objectManager->remove($listOwner);
        }

        $listEntries = $this->objectManager->getRepository('Phraseanet:UsrListEntry')
            ->findBy(['user' => $user]);

        foreach ($listEntries as $listEntry) {
            $this->objectManager->remove($listEntry);
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
        $sql = 'DELETE FROM `edit_presets` WHERE usr_id = :usr_id';
        $stmt = $this->appboxConnection->prepare($sql);
        $stmt->execute([':usr_id' => $user->getId()]);
        $stmt->closeCursor();

        $this->cleanSettings($user);
        $this->cleanTokens($user);
        $this->cleanQueries($user);
        $this->cleanFtpCredentials($user);
        $this->cleanOrders($user);
        $this->cleanFtpExports($user);
        $this->cleanAuthProvider($user);
        $this->cleanUserSessions($user);
        $this->cleanOauthApplication($user);
        $this->cleanLazarets($user);
        $this->cleanUsrList($user);
    }

    private function cleanLazarets(User $user)
    {
        $lazaretSessions = $this->objectManager->getRepository('Phraseanet:LazaretSession')->findBy(['user' => $user]);

        foreach ($lazaretSessions as $lazaretSession) {
            $this->objectManager->remove($lazaretSession);
        }
    }

    /**
     * Removes all user's rights, records right.
     *
     * @param User $user
     */
    private function cleanRights(User $user)
    {
        foreach ([
            'DELETE FROM `basusr` WHERE usr_id = :usr_id',
            'DELETE FROM `sbasusr` WHERE usr_id = :usr_id',
            'DELETE FROM `records_rights` WHERE usr_id = :usr_id',
        ] as $sql) {
            $stmt = $this->appboxConnection->prepare($sql);
            $stmt->execute([':usr_id' => $user->getId()]);
            $stmt->closeCursor();
        }
    }
    private function cleanOauthApplication(User $user)
    {
        $accounts = $this->objectManager->getRepository('Phraseanet:ApiAccount')->findByUser($user);

        foreach ($accounts as $account) {
            // remove ApiOauthCodes before ApiAccount
            $oauthCodes = $this->objectManager->getRepository('Phraseanet:ApiOauthCode')->findByAccount($account);
            foreach ($oauthCodes as $oauthCode) {
                $this->objectManager->remove($oauthCode);
            }

            $this->objectManager->remove($account);
        }

        $apps = $this->objectManager->getRepository('Phraseanet:ApiApplication')->findByCreator($user);

        foreach ($apps as $app) {
            $this->objectManager->remove($app);
        }
    }
}
