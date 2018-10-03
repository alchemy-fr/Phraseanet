<?php

namespace Alchemy\Phrasea\Core\Middleware;

use Assert\Assertion;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class SetupMiddlewareProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     * @param Application $app
     */
    public function register(Application $app)
    {
        Assertion::isInstanceOf($app, \Alchemy\Phrasea\Application::class);

        $app['setup.validate-config'] = $app->protect(function (Request $request) use ($app) {
            if (0 === strpos($request->getPathInfo(), '/setup')) {
                if (!$app['phraseanet.configuration-tester']->isInstalled()) {
                    if (!$app['phraseanet.configuration-tester']->isBlank()) {
                        if ('setup_upgrade_instructions' !== $app['request']->attributes->get('_route')) {
                            return $app->redirectPath('setup_upgrade_instructions');
                        }
                    }
                } elseif (!$app['phraseanet.configuration-tester']->isBlank()) {
                    return $app->redirectPath('homepage');
                }
            } else {
                if (false === strpos($request->getPathInfo(), '/include/minify')) {
                    $app['firewall']->requireSetup();
                }
            }
        });
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        // no-op
    }
}
