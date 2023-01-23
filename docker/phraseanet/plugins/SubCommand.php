<?php

namespace Alchemy\Docker\Plugins\Command;

function setupStreaming()
{
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
    if (function_exists('apache_setenv')) {
        apache_setenv('no-gzip', '1');
        apache_setenv('dont-vary', '1');
    }
}

setupStreaming();

abstract class SubCommand
{
    static public function run($cmd)
    {
        system($cmd, $return);
        if (0 !== $return) {
            throw new \Exception(sprintf('Error %d: %s', $return, $cmd));
        }
    }
}
