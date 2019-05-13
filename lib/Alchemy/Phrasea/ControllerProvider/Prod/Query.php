<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Controller\Prod\QueryController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Query implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.query'] = $app->share(function (PhraseaApplication $app) {
            return (new QueryController($app))
                ->setSearchEngineLocator(new LazyLocator($app, 'phraseanet.SE'))
                ->setSearchEngineLoggerLocator(new LazyLocator($app, 'phraseanet.SE.logger'))
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $controllers->post('/', 'controller.prod.query:query')
            ->bind('prod_query');

        $controllers->post('/completion/', 'controller.prod.query:completion');

        $controllers->post('/answer-train/', 'controller.prod.query:queryAnswerTrain')
            ->bind('preview_answer_train');

        $controllers->post('/reg-train/', 'controller.prod.query:queryRegTrain')
            ->bind('preview_reg_train');

        return $controllers;
    }
}
