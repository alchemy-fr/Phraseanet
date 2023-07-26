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

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Setup\RequirementCollection;

class FilesystemRequirements extends RequirementCollection implements RequirementInterface
{
    public function __construct(PropertyAccess $conf)
    {
        $baseDir = realpath(__DIR__ . '/../../../../../');

        $this->setName('Filesystem');

        $paths = [
            $baseDir . '/config',
            $baseDir . '/config/stamp',
            $baseDir . '/config/status',
            $baseDir . '/config/minilogos',
            $baseDir . '/config/wm',
            $conf->get(['main', 'storage', 'log']),
            $baseDir . '/tmp',
            $baseDir . '/tmp/locks',
            $conf->get(['main', 'storage', 'caption']),
            $conf->get(['main', 'storage', 'lazaret']),
            $conf->get(['main', 'storage', 'download']),
            $conf->get(['main', 'storage', 'cache']),
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
