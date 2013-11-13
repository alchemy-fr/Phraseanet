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

class patch_381alpha2a implements patchInterface
{
    /** @var string */
    private $release = '3.8.1-alpha.2';

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
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return array();
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
        if (false !== strpos($app['phraseanet.registry']->get('GV_i18n_service'), 'localization.webservice.alchemyasp.com')) {
            $app['phraseanet.registry']->set('GV_i18n_service', 'http://geonames.alchemyasp.com/', \registry::TYPE_STRING);
        }

        return true;
    }
}
