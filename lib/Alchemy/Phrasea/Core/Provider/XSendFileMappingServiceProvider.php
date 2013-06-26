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
use Silex\Application;
use Silex\ServiceProviderInterface;

class XSendFileMappingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['xsendfile.mapping'] = $app->share(function(Application $app) {
            $mapping = array();

            if (isset($app['phraseanet.configuration']['xsendfile']['mapping'])) {
                $mapping = $app['phraseanet.configuration']['xsendfile']['mapping'];
            }

            $mapping[] = array(
                'directory' => $app['root.path'] . '/tmp/download/',
                'mount-point' => '/download/',
            );
            $mapping[] = array(
                'directory' => $app['root.path'] . '/tmp/lazaret/',
                'mount-point' => '/lazaret/',
            );

            return $mapping;
        });

        $app['phraseanet.xsendfile-mapping'] = $app->share(function($app) {
            return new Mapping($app['xsendfile.mapping']);
        });
    }

    public function boot(Application $app)
    {
    }
}
