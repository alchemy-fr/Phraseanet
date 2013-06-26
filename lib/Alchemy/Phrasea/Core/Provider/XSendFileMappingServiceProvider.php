<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\XSendFile\Mapping;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Silex\Application;
use Silex\ServiceProviderInterface;

class XSendFileMappingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['xsendfile.mapping'])) {
            $app['xsendfile.mapping'] = array();
        }

        if (!is_array($app['xsendfile.mapping'])) {
            throw new InvalidArgumentException('XSendFile mapping must be an array');
        }

        $app['phraseanet.xsendfile-mapping'] = $app->share(function($app) {
            $mapping = array();
            foreach($app['xsendfile.mapping'] as $path => $mountPoint) {
                $mapping[] = array(
                    'directory' => $path,
                    'mount-point' => $mountPoint,
                );
            }

            return Mapping::create($app, $mapping);
        });
    }

    public function boot(Application $app)
    {
    }
}
