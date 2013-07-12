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
use Symfony\Component\Process\ExecutableFinder;

class patch_3818 implements patchInterface
{
    /** @var string */
    private $release = '3.8.0.a18';

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
        $finder = new ExecutableFinder();

        $binaries = $app['phraseanet.configuration']['binaries'];
        $binaries['recess_binary'] = $finder->find('recess');
        $app['phraseanet.configuration']['binaries'] = $binaries;

        return true;
    }
}
