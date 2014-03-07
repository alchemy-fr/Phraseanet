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

class patch_380alpha16a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.16';

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
    public function apply(\appbox $appbox, Application $app)
    {
        $xsendfile = $app['conf']->get('xsendfile');

        if (!isset($xsendfile['mapping'])) {
            $xsendfile['mapping'] = [];
        }

        $xsendfile['mapping'][] = [
            'directory' => $app['root.path'] . '/tmp/lazaret/',
            'mount-point' => '/lazaret/',
        ];
        $xsendfile['mapping'][] = [
            'directory' => $app['root.path'] . '/tmp/download/',
            'mount-point' => '/download/',
        ];

        $app['conf']->set('xsendfile', $xsendfile);

        return true;
    }
}
