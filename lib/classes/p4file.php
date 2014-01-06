<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class p4file
{

    public static function apache_tokenize(PropertyAccess $conf, $file)
    {
        $ret = false;

        if ($conf->get(['registry', 'executables', 'h264-streaming-enabled']) && is_file($file)) {
            if (mb_strpos($file, $conf->get(['registry', 'executables', 'auth-token-directory-path'])) === false) {
                return false;
            }

            $server = new system_server();

            if ($server->is_nginx()) {
                $fileToProtect = mb_substr($file, mb_strlen($conf->get(['registry', 'executables', 'auth-token-directory-path'])));

                $secret = $conf->get(['registry', 'executables', 'auth-token-passphrase']);
                $protectedPath = p4string::addFirstSlash(p4string::delEndSlash($conf->get(['registry', 'executables', 'auth-token-directory'])));

                $hexTime = strtoupper(dechex(time() + 3600));

                $token = md5($protectedPath . $fileToProtect . '/' . $secret . '/' . $hexTime);

                $url = $protectedPath . $fileToProtect . '/' . $token . '/' . $hexTime;

                $ret = $url;
            } elseif ($server->is_apache()) {
                $fileToProtect = mb_substr($file, mb_strlen($conf->get(['registry', 'executables', 'auth-token-directory-path'])));

                $secret = $conf->get(['registry', 'executables', 'auth-token-passphrase']);        // Same as AuthTokenSecret
                $protectedPath = p4string::addEndSlash(p4string::delFirstSlash($conf->get(['registry', 'executables', 'auth-token-directory'])));         // Same as AuthTokenPrefix
                $hexTime = dechex(time());             // Time in Hexadecimal

                $token = md5($secret . $fileToProtect . $hexTime);

                // We build the url
                $url = '/' . $protectedPath . $token . "/" . $hexTime . $fileToProtect;

                $ret = $url;
            }
        }

        return $ret;
    }

}
