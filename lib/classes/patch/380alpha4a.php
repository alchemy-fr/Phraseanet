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
use Alchemy\Phrasea\Model\Entities\AuthFailure;

class patch_380alpha4a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.4';

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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return ['auth-failure'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(\appbox $appbox, Application $app)
    {
        $conn = $app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT date, login, ip, locked
                FROM badlog
                ORDER BY id ASC';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 1;

        foreach ($rs as $row) {
            $date = Datetime::createFromFormat('Y-m-d h:i:s', $row['date']);
            $failure = new AuthFailure();
            if ($date) {
                $failure->setCreated($date);
            }
            $failure->setIp($row['ip']);
            $failure->setLocked(!!$row['locked']);
            $failure->setUsername($row['login']);

            $app['EM']->persist($failure);

            if (0 === $n++ % 1000) {
                $app['EM']->flush();
                $app['EM']->clear();
            }
        }

        $app['EM']->flush();
        $app['EM']->clear();

        return true;
    }
}
