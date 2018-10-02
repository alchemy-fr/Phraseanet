<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class http_query
{

    /**
     *
     * @param  string $url
     * @return int
     */
    public static function getHttpCodeFromUrl($url)
    {
        if ( ! is_scalar($url)) {
            return null;
        }

        if (trim($url) === '') {
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        curl_exec($ch);
        $result = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $result;
    }

    public static function getHttpHeaders($url)
    {
        if ( ! is_scalar($url)) {
            return null;
        }

        if (trim($url) === '') {
            return null;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);

        curl_exec($ch);
        $result = curl_getinfo($ch);
        curl_close($ch);

        return $result;
    }

    /**
     *
     * @param  string $url
     * @param  array  $post_data
     * @return string
     */
    public static function getUrl($url, $post_data = false)
    {
        if ( ! is_scalar($url)) {
            return null;
        }

        if (trim($url) === '') {
            return null;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);

        if ($post_data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $result = (curl_exec($ch));
        curl_close($ch);

        return $result;
    }
}
