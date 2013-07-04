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

class patch_3816 implements patchInterface
{
    /** @var string */
    private $release = '3.8.0.a16';

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
        $xsendfile = $app['phraseanet.configuration']['xsendfile'];

        if (!isset($xsendfile['mapping'])) {
            $xsendfile['mapping'] = array();
        }

        $xsendfile['mapping'][] = array(
            'directory' => $app['root.path'] . '/tmp/lazaret/',
            'mount-point' => '/lazaret/',
        );
        $xsendfile['mapping'][] = array(
            'directory' => $app['root.path'] . '/tmp/download/',
            'mount-point' => '/download/',
        );

        $app['phraseanet.configuration']['xsendfile'] = $xsendfile;

        return true;
    }
}
