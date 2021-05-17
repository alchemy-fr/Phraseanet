<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\User;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\User\UserPreferenceController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;


class Preferences implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.user.preferences'] = $app->share(function (PhraseaApplication $app) {
            return (new UserPreferenceController($app));
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        /* @uses UserPreferenceController::saveUserPref */
        $controllers->post('/', 'controller.user.preferences:saveUserPref')
            ->bind('save_pref');

        /* @uses UserPreferenceController::saveTemporaryPref */
        $controllers->post('/temporary/', 'controller.user.preferences:saveTemporaryPref')
            ->bind('save_temp_pref');

        return $controllers;
    }
}
