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

class patch_380alpha16a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.16';

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
        $xsendfile = $app['conf']->get('xsendfile');

        if (!isset($xsendfile['mapping'])) {
            $xsendfile['mapping'] = [];
        }

        $xsendfile['mapping'][] = [
            'directory' => $app['tmp.lazaret.path'],
            'mount-point' => '/lazaret/',
        ];
        $xsendfile['mapping'][] = [
            'directory' => $app['tmp.download.path'],
            'mount-point' => '/download/',
        ];

        $app['conf']->set('xsendfile', $xsendfile);

        return true;
    }
}
