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
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\Controller\Prod\EditController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Edit implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.edit'] = $app->share(function (PhraseaApplication $app) {
            return (new EditController($app))
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
                ->setDispatcher($app['dispatcher'])
                ->setSubDefinitionSubstituerLocator(new LazyLocator($app, 'subdef.substituer'))
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
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall
                ->requireNotGuest()
                ->requireRight(\ACL::CANMODIFRECORD);
        });

        $controllers->post('/', 'controller.prod.edit:submitAction');

        $controllers->get('/vocabulary/{vocabulary}/', 'controller.prod.edit:searchVocabularyAction');

        $controllers->post('/apply/', 'controller.prod.edit:applyAction');

        $controllers->get('/presets/{preset_id}', 'controller.prod.edit:presetsLoadAction');
        $controllers->get('/presets', 'controller.prod.edit:presetsListAction');
        $controllers->delete('/presets/{preset_id}', 'controller.prod.edit:presetsDeleteAction');
        $controllers->post('/presets', 'controller.prod.edit:presetsSaveAction');

        return $controllers;
    }
}
