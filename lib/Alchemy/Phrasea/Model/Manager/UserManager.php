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

use Alchemy\Geonames\Connector as GeonamesConnector;
use Alchemy\Geonames\Exception\ExceptionInterface as GeonamesExceptionInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Entities\User;
use Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface;

class UserManager implements ManagerInterface
{
    /** @var \appbox */
    protected $appbox;
    /** @var ObjectManager */
    protected $objectManager;
    /** @var PasswordEncoderInterface */
    protected $passwordEncoder;
    /** @var GeonamesConnector */
    private $geonamesConnector;

    /**
     * Constructor
     *
     * @param PasswordEncoderInterface $passwordEncoder
     * @param GeonamesConnector $connector
     * @param ObjectManager $om
     * @param \appbox $appbox
     */
    public function __construct(PasswordEncoderInterface $passwordEncoder, GeonamesConnector $connector, ObjectManager $om, \appbox $appbox)
    {
        $this->appbox = $appbox;
        $this->objectManager = $om;
        $this->passwordEncoder = $passwordEncoder;
        $this->geonamesConnector = $connector;
    }
    
    /**
     * @{inheritdoc}
     */
    public function create()
    {
        return new User();
    }
    
    /**
     * @{inheritdoc}
     */
    public function delete($user, $flush = true)
    {
        $user->setDeleted(true);
        $user->setEmail(null);
        $user->setLogin(sprintf('(#deleted_%s', $user->getLogin()));

        $this->cleanRelations($user);

        $this->objectManager->persist($user);
        if ($flush) {
            $this->objectManager->flush();
        }
    }

    /**
     * @{inheritdoc}
     */
    public function update($user, $flush = true)
    {
        $this->objectManager->persist($user);
        if ($flush) {
            $this->objectManager->flush();
        }
    }

    /**
     * Updates the modelOf field from the template field value.
     *
     * @param UserInterface $user
     */
    public function onUpdateModel(User $user)
    {
        $user->getFtpCredential()->resetCredentials();
        $this->cleanSettings($user);
        $user->reset();
    }

    /**
     * Updates the password field from the plain password field value.
     *
     * @param UserInterface $user
     */
    public function onUpdatePassword(User $user)
    {
        $user->setNonce(base_convert(sha1(uniqid(mt_rand(), true)), 16, 36));
        $user->setPassword($this->passwordEncoder->encodePassword($user->getPassword(), $user->getNonce()));
    }

    /**
     * Updates the country fields for a user according to the current geoname id field value.
     *
     * @param User $user
     */
    public function onUpdateGeonameId(User $user)
    {
        if (null !== $user->getGeonameId()) {
            try {
                $country = $this->geonamesConnector
                    ->geoname($user->getGeonameId())
                    ->get('country');

                if (isset($country['name'])) {
                    $user->setCountry($country['name']);
                }
            } catch (GeonamesExceptionInterface $e) {

            }
        }
    }

    /**
     * Removes user settings.
     *
     * @param User $user
     */
    public function cleanSettings(User $user)
    {
        foreach($user->getNotificationSettings() as $userNotificatonSetting) {
            $userNotificatonSetting->setUser(null);
        }

        $user->getNotificationSettings()->clear();

        foreach($user->getSettings() as $userSetting) {
            $userSetting->setUser(null);
        }

        $user->getSettings()->clear();
    }

    /**
     * Removes user queries.
     *
     * @param User $user
     */
    public function cleanQueries(User $user)
    {
        foreach($user->getQueries() as $userQuery) {
            $userQuery->setUser(null);
        }

        $user->getQueries()->clear();
    }

    /**
     * Removes all user's relations.
     * 
     * @todo Removes order relationship, it is now a doctrine entity.
     *
     * @param User $user
     */
    private function cleanRelations(User $user)
    {
        $conn = $this->appbox->get_connection();
        foreach(array(
            'basusr',
            'sbasusr',
            'edit_presets',
            'ftp_export',
            'order',
            'sselnew',
            'tokens',
        ) as $table) {
            $stmt = $conn->prepare('DELETE FROM `' .$table. '` WHERE usr_id = :usr_id');
            $stmt->execute(array(':usr_id' => $user->getId()));
            $stmt->closeCursor();
        }
        unset($stmt);

        $this->cleanSettings($user);
        $this->cleanQueries($user);
    }
}
