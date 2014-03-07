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
use Alchemy\Phrasea\Model\Entities\Preset;
use Gedmo\Timestampable\TimestampableListener;

class patch_390alpha16a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.16';

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
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(\appbox $appbox, Application $app)
    {
        $sql = ' SELECT edit_preset_id, creation_date, title, xml
                 FROM edit_presets';

        $em = $app['EM'];
        $n = 0;
        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());
        foreach ($app['phraseanet.appbox']->get_connection()->fetchAll($sql) as $row) {
            if (null === $user = $this->loadUser($app['EM'], $row['usr_id'])) {
                continue;
            }
            $preset = new Preset();
            $preset->setUser($user);
            $preset->setSbasId($row['sbas_id']);
            $preset->setTitle($row['title']);
            $fields = [];
            $preset->setData($fields);

            $em->persist($preset);
            $n++;

            if ($n % 20 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        $em->getEventManager()->addEventSubscriber(new TimestampableListener());

        return true;
    }
}
