<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Vocabulary;

use Alchemy\Phrasea\Application;

/**
 * Vocabulary Controller
 *
 * Various methods fro controlling vocabularies inside Phraseanet
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Controller
{

    /**
     * Factory of ControlProvider
     *
     * @param string $type
     * @return \Alchemy\Phrasea\Vocabulary\ControlProvider\ControlProviderInterface
     * @throws \Exception when ControlProvider is not found
     */
    public static function get(Application $app, $type)
    {
        $classname = __NAMESPACE__ . '\\ControlProvider\\' . $type . 'Provider';

        if ( ! class_exists($classname)) {
            throw new \Exception('Vocabulary type not found');
        }

        return new $classname($app);
    }

    /**
     * Returns an array of available ControlProviders
     *
     * @return array
     */
    public static function getAvailable(Application $app)
    {
        return array(
            new ControlProvider\UserProvider($app)
        );
    }
}
