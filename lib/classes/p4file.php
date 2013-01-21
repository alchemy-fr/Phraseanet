<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class p4file
{

    public static function apache_tokenize(\registry $registry, $file)
    {
        $ret = false;

        if ($registry->get('GV_h264_streaming') && is_file($file)) {
            if (($pos = mb_strpos($file, $registry->get('GV_mod_auth_token_directory_path'))) === false) {
                return false;
            }

            $server = new system_server();

            if ($server->is_nginx()) {
                $fileToProtect = mb_substr($file, mb_strlen($registry->get('GV_mod_auth_token_directory_path')));

                $secret = $registry->get('GV_mod_auth_token_passphrase');
                $protectedPath = p4string::addFirstSlash(p4string::delEndSlash($registry->get('GV_mod_auth_token_directory')));

                $hexTime = strtoupper(dechex(time() + 3600));

                $token = md5($protectedPath . $fileToProtect . '/' . $secret . '/' . $hexTime);

                $url = $protectedPath . $fileToProtect . '/' . $token . '/' . $hexTime;

                $ret = $url;
            } elseif ($server->is_apache()) {
                $fileToProtect = mb_substr($file, mb_strlen($registry->get('GV_mod_auth_token_directory_path')));

                $secret = $registry->get('GV_mod_auth_token_passphrase');        // Same as AuthTokenSecret
                $protectedPath = p4string::addEndSlash(p4string::delFirstSlash($registry->get('GV_mod_auth_token_directory')));         // Same as AuthTokenPrefix
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
