<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Entities\User;
use Gedmo\Timestampable\TimestampableListener;

class patch_3902 implements patchInterface
{
    /** @var string */
    private $release = '3.9.0.a2';

    /** @var array */
    private $concern = array(base::APPLICATION_BOX);

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $conn = $app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT * FROM usr';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['EM'];
        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());

        foreach ($rs as $row) {
            $user = new User();
            $user->setActivity($row['activite']);
            $user->setAddress($row['adresse']);
            $user->setAdmin(!!$row['create_db']);
            $user->setCanChangeFtpProfil(!!$row['canchgftpprofil']);
            $user->setCanChangeProfil(!!$row['canchgprofil']);
            $user->setCity($row['ville']);
            $user->setCompany($row['societe']);
            $user->setCountry((string) $row['pays']);
            $user->setEmail($row['usr_mail']);
            $user->setFax($row['fax']);
            $user->setFirstName($row['usr_prenom']);
            if ($row['geonameid'] > 0) {
                $user->setGeonameId($row['geonameid']);
            }
            $user->setGuest(!!$row['invite']);
            $user->setJob($row['fonction']);
            $user->setLastConnection(new \DateTime($row['last_conn']));
            $user->setLastModel($row['lastModel']);
            $user->setLastName($row['usr_nom']);
            $user->setLdapCreated(!!$row['ldap_created']);
            try {
                $user->setLocale($row['locale']);
            } catch (\InvalidArgumentException $e ) {

            }
            $user->setLogin($row['usr_login']);

            if (substr($row['usr_login'], 0, 10) === '(#deleted_') {
                $user->setDeleted(true);
            }

            $user->setMailLocked(!!$row['mail_locked']);
            $user->setMailNotificationsActivated(!!$row['mail_notifications']);
            $user->setModelOf($row['model_of']);
            $user->setNonce($row['nonce']);
            $user->setPassword($row['usr_password']);
            $user->setPushList($row['push_list']);
            $user->setRequestNotificationsActivated(!!$row['request_notifications']);
            $user->setSaltedPassword(!!$row['salted_password']);

            switch ($row['usr_sexe']) {
                case 0:
                    $gender = User::GENDER_MISS;
                    break;
                case 1:
                    $gender = User::GENDER_MRS;
                    break;
                case 2:
                    $gender = User::GENDER_MR;
                    break;
                default:
                    $gender = null;
            }

            $user->setGender($gender);
            $user->setPhone($row['tel']);
            $user->setTimezone($row['timezone']);
            $user->setZipCode($row['cpostal']);
            $user->setCreated(new \DateTime($row['usr_creationdate']));
            $user->setupdated(new \DateTime($row['usr_modificationdate']));

            $em->persist($user);

            $n++;

            if ($n % 100 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        $em->getEventManager()->addEventSubscriber(new TimestampableListener());
    }
}
