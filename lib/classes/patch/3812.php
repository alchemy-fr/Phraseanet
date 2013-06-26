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

class patch_3812 implements patchInterface
{
    /** @var string */
    private $release = '3.8.0.a12';

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
        $binaries = $app['phraseanet.configuration']['binaries'];

        foreach ($binaries as $name => $value) {
            if ('' === $value) {
                $binaries[$name] = null;
            }
        }

        $binaries['ffmpeg_timeout'] = 3600;
        $binaries['ffprobe_timeout'] = 60;
        $binaries['gs_timeout'] = 60;
        $binaries['mp4box_timeout'] = 60;
        $binaries['swftools_timeout'] = 60;
        $binaries['unoconv_timeout'] = 60;

        $app['phraseanet.configuration']['binaries'] = $binaries;

        return true;
    }
}
