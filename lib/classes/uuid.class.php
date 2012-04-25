<?php

/*
 * @author Andrew Moore
 *
 * Originally found at: http://www.php.net/manual/en/function.uniqid.php#94959
 */

class uuid
{

    /**
     * @desc UUID named based version that use MD5(hash)
     * @param string $namespace
     * @param string $name
     * @return string
     */
    public static function generate_v3($namespace, $name)
    {
        if ( ! self::is_valid($namespace)) {
            return false;
        }

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-', '{', '}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = md5($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
                // 32 bits for "time_low"
                       substr($hash, 0, 8),
                // 16 bits for "time_mid"
                              substr($hash, 8, 4),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 3
                                     (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                                                    (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
                // 48 bits for "node"
                                                                   substr($hash, 20, 12)
        );
    }

    /**
     * @desc UUID randomly generated version
     * @return string
     */
    public static function generate_v4()
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                // 32 bits for "time_low"
                       mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                // 16 bits for "time_mid"
                                                   mt_rand(0, 0xffff),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                                                           mt_rand(0, 0x0fff) | 0x4000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                                                                   mt_rand(0, 0x3fff) | 0x8000,
                // 48 bits for "node"
                                                                           mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * @desc UUID named version that use SHA-1 hashing
     * @param string $namespace
     * @param string $name
     * @return string
     */
    public static function generate_v5($namespace, $name)
    {
        if ( ! self::is_valid($namespace)) {
            return false;
        }

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-', '{', '}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = sha1($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
                // 32 bits for "time_low"
                       substr($hash, 0, 8),
                // 16 bits for "time_mid"
                              substr($hash, 8, 4),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 5
                                     (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                                                    (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
                // 48 bits for "node"
                                                                   substr($hash, 20, 12)
        );
    }

    /**
     * @desc check if an uuid is a valid one
     * @param string $uuid
     * @return boolean
     */
    public static function is_valid($uuid)
    {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' .
                '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
    }

    /**
     * @desc Compare two UUID's "lexically"
     * @param stiring $uuid1
     * @param string $uuid2
     * @return int
     * -1 uuid1<uuid2 0 uuid1==uuid2 +1 uuid1>uuid2
     */
    public static function compare($uuid1, $uuid2)
    {
        return (strcmp($uuid1, $uuid2) === 0);
    }

    /**
     * @desc Check wheter an UUID is the NULL UUID 00000000-0000-0000-0000-000000000000
     * @return  string
     */
    public static function is_null($uuid)
    {
        return (0 === strcmp($uuid, '00000000-0000-0000-0000-000000000000'));
    }
}
