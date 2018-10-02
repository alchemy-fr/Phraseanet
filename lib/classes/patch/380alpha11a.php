<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Entities\SessionModule;
use Doctrine\DBAL\DBALException;

class patch_380alpha11a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.11';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

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
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return ['20131118000009', '20131118000006'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        try {
            $sql = 'SELECT usr_id, user_agent, ip, platform, browser, app,
                        browser_version, screen, token, nonce, lastaccess, created_on
                    FROM cache';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();
        } catch (DBALException $e) {
            // this may fail on oldest versions
            return false;
        }

        foreach ($rs as $row) {
            if (null === $user = $this->loadUser($app['orm.em'], $row['usr_id'])) {
                continue;
            }

            $created = $updated = null;
            if ('0000-00-00 00:00:00' !== $row['created_on']) {
                $created = \DateTime::createFromFormat('Y-m-d H:i:s', $row['created_on']);
            }
            if ('0000-00-00 00:00:00' !== $row['lastaccess']) {
                $updated = \DateTime::createFromFormat('Y-m-d H:i:s', $row['lastaccess']);
            }

            $session = new Session();
            $session
                ->setUser($user)
                ->setUserAgent($row['user_agent'])
                ->setUpdated($updated)
                ->setToken($row['token'])
                ->setPlatform($row['platform'])
                ->setNonce($row['nonce'])
                ->setIpAddress($row['ip'])
                ->setCreated($created)
                ->setBrowserVersion($row['browser_version'])
                ->setBrowserName($row['browser']);

            $sizes = explode ('x', $row['screen']);

            if (2 === count($sizes)) {
                $session
                    ->setScreenWidth($sizes[0])
                    ->setScreenHeight($sizes[1]);
            }

            if (false !== $apps = @unserialize($row['app'])) {
                foreach ($apps as $appli) {
                    $module = new SessionModule();
                    $module
                        ->setModuleId($appli)
                        ->setCreated($created)
                        ->setSession($session)
                        ->setUpdated($updated);

                    $session->addModule($module);

                    $app['orm.em']->persist($module);
                }
            }

            $app['orm.em']->persist($session);
        }

        $app['orm.em']->flush();

        return true;
    }
}
