<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Loader\Autoloader;
use Alchemy\Phrasea\Loader\CacheAutoloader;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class bootstrap
{
    protected static $autoloader_initialized;

    public static function register_autoloads($cacheAutoload = false)
    {
        if (static::$autoloader_initialized) {
            return;
        }

        require_once __DIR__ . '/../Alchemy/Phrasea/Loader/Autoloader.php';
        require_once __DIR__ . '/../Alchemy/Phrasea/Loader/Autoloader.php';

        if ($cacheAutoload === true) {
            try {
                require_once __DIR__ . '/../Alchemy/Phrasea/Loader/CacheAutoloader.php';

                $prefix = 'class_';
                $namespace = md5(__DIR__);

                $loader = new CacheAutoloader($prefix, $namespace);
            } catch (\Exception $e) {
                //no op code cache available
                $loader = new Autoloader();
            }
        } else {
            $loader = new Autoloader();
        }

        $getComposerNamespaces = function() {
                return require realpath(__DIR__ . '/../../vendor/composer/autoload_namespaces.php');
            };

        foreach ($getComposerNamespaces() as $prefix => $path) {
            if (substr($prefix, -1) === '_' || $prefix == 'Pimple') {
                $loader->registerPrefix($prefix, $path);
            } else {
                $loader->registerNamespace($prefix, $path);
            }
        }

        $loader->registerNamespaces(array(
            'Entities'         => realpath(__DIR__ . '/../Doctrine/'),
            'Repositories'     => realpath(__DIR__ . '/../Doctrine/'),
            'Proxies'          => realpath(__DIR__ . '/../Doctrine/'),
            'Doctrine\\Logger' => realpath(__DIR__ . '/../'),
            'Types'            => realpath(__DIR__ . "/../Doctrine"),
            'PhraseaFixture'   => realpath(__DIR__ . "/../conf.d"),
        ));

        $loader->register();

        set_include_path(
            get_include_path() . PATH_SEPARATOR . realpath(__DIR__ . '/../../vendor/zend/gdata/library')
        );

        static::$autoloader_initialized = true;

        return;
    }
}
