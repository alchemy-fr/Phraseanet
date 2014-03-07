<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\UserSetting;

class patch_390alpha4a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.4';

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
    public function getDoctrineMigrations()
    {
        return ['user-setting'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(\appbox $appbox, Application $app)
    {
        $sql = 'DELETE FROM UserSettings';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT * FROM usr_settings';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['EM'];

        foreach ($rs as $row) {
            if (substr($row['prop'], 0, 13) === "notification_") {
                continue;
            }

            if (null === $user = $this->loadUser($app['EM'], $row['usr_id'])) {
                continue;
            }

            $userSetting = new UserSetting();
            $userSetting->setName($row['prop']);
            $userSetting->setValue($row['value']);
            $userSetting->setUser($user);

            $em->persist($userSetting);

            $n++;

            if ($n % 1000 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        return true;
    }
}
