<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Filesystem;

use Alchemy\Phrasea\Application;

class ApplicationPathServiceGenerator
{
    public function createDefinition(array $key, callable $default)
    {
        return function(Application $app) use ($key, $default) {
            static $path;

            if (null === $path) {
                $path = ($app['phraseanet.configuration']->isSetup())
                    ? $app['conf']->get($key)
                    : null;

                if (null === $path) {
                    $path = $default($app);
                }

                // ensure path is created
                $app['filesystem']->mkdir($path);
            }

            return $path;
        };
    }
}
