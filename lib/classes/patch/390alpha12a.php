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
use Alchemy\Phrasea\Plugin\PluginManager;

class patch_390alpha12a extends patchAbstract
{
    /** @var string */
    private $release = '3.9.0-alpha.12';

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
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        /** @var PluginManager $manager */
        $manager = $app['plugins.manager'];
        foreach ($app['conf']->get('plugins', []) as $name => $parameters) {
            $manager->disablePlugin($name);
        }
    }
}
