<?php

namespace Alchemy\Phrasea\Setup\Requirements;

use Alchemy\Phrasea\Setup\System\RequirementCollection;

class FilesystemRequirements extends RequirementCollection
{
    public function __construct()
    {
        $baseDir = realpath(__DIR__ . '/../../../../../');

        $this->setName('Filesystem');

        $paths = array(
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
            $baseDir . '/tmp/cache_minify',
            $baseDir . '/tmp/lazaret',
            $baseDir . '/tmp/desc_tmp',
            $baseDir . '/tmp/download',
            $baseDir . '/tmp/batches'
        );

        foreach ($paths as $path) {
            $this->addRequirement(
                is_writable($path),
                "$path directory must be writable",
                "Change the permissions of the \"<strong>$path</strong>\" directory so that the web server can write into it."
            );
        }
    }
}
