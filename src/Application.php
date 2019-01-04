<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

class Application
{

    const ENV_DEV = 'dev';
    const ENV_PROD = 'prod';
    const ENV_TEST = 'test';

    protected static $availableLanguages = [
        'de' => 'Deutsch',
        'en' => 'English',
        'fr' => 'Français',
        'nl' => 'Dutch',
    ];

    private static $flashTypes = ['warning', 'info', 'success', 'error'];

    /**
     * Return available language for phraseanet
     *
     * @return array
     */
    public static function getAvailableLanguages()
    {
        return static::$availableLanguages;
    }

    /**
     * Returns available flash message types for Phraseanet
     *
     * @return array
     */
    public static function getAvailableFlashTypes()
    {
        return static::$flashTypes;
    }
}
