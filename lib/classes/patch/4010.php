<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Alchemy\Phrasea\Application;


class patch_4010 implements patchInterface
{
    /** @var string */
    private $release = '4.0.10';

    /** @var array */
    private $concern = [base::DATA_BOX];

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
    public function getDoctrineMigrations()
    {
        return [];
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
    public function apply(base $databox, Application $app)
    {
         foreach ($databox->get_meta_structure() as $databox_field) {
            if ($databox_field->get_tbranch() != '') {
                $databox_field->set_generate_cterms(true);
            } else {
                $databox_field->set_generate_cterms(false);
            }

            $databox_field->save();
        }

        return true;
    }
}
