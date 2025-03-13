<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Model\Repositories\PsSettings\Expose;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PsSettingsServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['ps_settings.expose'] = $app->share(function ($app) {
            return new Expose($app['repo.ps_settings'], $app['repo.ps_settingkeys']);
        });
    }

    public function boot(Application $app)
    {
    }
}
