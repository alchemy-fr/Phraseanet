<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\RequirementCollection;

class FilesystemRequirements extends RequirementCollection implements RequirementInterface
{
    public function __construct()
    {
        $baseDir = realpath(__DIR__ . '/../../../../../');

        $this->setName('Filesystem');

        $paths = [
            $baseDir . '/config',
            $baseDir . '/config/stamp',
            $baseDir . '/config/status',
            $baseDir . '/config/minilogos',
            $baseDir . '/config/templates',
            $baseDir . '/config/topics',
            $baseDir . '/config/wm',
            $baseDir . '/logs',
            $baseDir . '/tmp',
            $baseDir . '/www/custom',
            $baseDir . '/tmp/locks',
            $baseDir . '/tmp/cache_twig',
            $baseDir . '/tmp/serializer',
            $baseDir . '/tmp/doctrine',
            $baseDir . '/tmp/cache_minify',
            $baseDir . '/tmp/lazaret',
            $baseDir . '/tmp/desc_tmp',
            $baseDir . '/tmp/download',
            $baseDir . '/tmp/batches'
        ];

        foreach ($paths as $path) {
            $this->addRequirement(
                is_writable($path),
                "$path directory must be writable",
                "Change the permissions of the \"<strong>$path</strong>\" directory so that the web server can write into it."
            );
        }
    }
}
