<?php

namespace Alchemy\Phrasea;

use Symfony\Component\Filesystem\Filesystem;

class ComposerScripts
{
    public static function postUpdate()
    {
        static::overwriteMinifyGroupsConfig();
    }

    public static function postInstall()
    {
        static::overwriteMinifyGroupsConfig();
    }

    private static function overwriteMinifyGroupsConfig()
    {
        $filesystem = new Filesystem();

        $source = __DIR__ . '/../../conf.d/groupsConfig.php';
        $target = __DIR__ . '/../../../vendor/mrclay/minify/min/groupsConfig.php';

        $filesystem->remove($target);
        $filesystem->copy($source, $target);

        $source = __DIR__ . '/../../conf.d/config.php';
        $target = __DIR__ . '/../../../vendor/mrclay/minify/min/config.php';

        $filesystem->remove($target);
        $filesystem->copy($source, $target);
    }
}
