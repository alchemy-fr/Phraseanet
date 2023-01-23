<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
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
            $baseDir . '/config/wm',
            $baseDir . '/logs',
            $baseDir . '/tmp',
            $baseDir . '/tmp/locks',
            $baseDir . '/tmp/caption',
            $baseDir . '/tmp/lazaret',
            $baseDir . '/tmp/download',
            $baseDir . '/cache',
            $baseDir . '/www/custom',
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
