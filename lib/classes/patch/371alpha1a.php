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

class patch_371alpha1a extends patchAbstract
{
    /** @var string */
    private $release = '3.7.1-alpha1';

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
        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_meta_structure() as $databox_field) {
                $databox_field->save();
            }
        }

        return true;
    }
}
