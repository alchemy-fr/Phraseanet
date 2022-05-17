<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\LightboxController;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class Lightbox implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.lightbox'] = $app->share(function (PhraseaApplication $app) {
            return (new LightboxController($app))
                ->setDispatcher($app['dispatcher']);
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createCollection($app);

        $controllers->before([$this, 'redirectOnLogRequests']);

        $firewall = $this->getFirewall($app);
        $firewall->addMandatoryAuthentication($controllers);

        $controllers
            // Silex\Route::convert is not used as this should be done prior the before middleware
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-access']);

        $controllers->get('/', 'controller.lightbox:rootAction')
            ->bind('lightbox')
        ;

        $controllers->get('/ajax/NOTE_FORM/{sselcont_id}/', 'controller.lightbox:ajaxNoteFormAction')
            ->bind('lightbox_ajax_note_form')
            ->assert('sselcont_id', '\d+')
        ;

        $controllers->get('/ajax/LOAD_BASKET_ELEMENT/{sselcont_id}/', 'controller.lightbox:ajaxLoadBasketElementAction')
            ->bind('lightbox_ajax_load_basketelement')
            ->assert('sselcont_id', '\d+')
        ;

        $controllers->get('/ajax/LOAD_FEED_ITEM/{entry_id}/{item_id}/', 'controller.lightbox:ajaxLoadFeedItemAction')
            ->bind('lightbox_ajax_load_feeditem')
            ->assert('entry_id', '\d+')
            ->assert('item_id', '\d+')
        ;

        $controllers->get('/validate/{basket}/', 'controller.lightbox:validationAction')
            ->bind('lightbox_validation')
            ->assert('basket', '\d+')
        ;

        /** @uses LightboxController::compareAction() */
        $controllers->get('/compare/{basket}/', 'controller.lightbox:compareAction')
            ->bind('lightbox_compare')
            ->assert('basket', '\d+');

        $controllers->get('/feeds/entry/{entry_id}/', 'controller.lightbox:getFeedEntryAction')
            ->bind('lightbox_feed_entry')
            ->assert('entry_id', '\d+')
        ;

        $controllers->get('/ajax/LOAD_REPORT/{basket}/', 'controller.lightbox:ajaxReportAction')
            ->bind('lightbox_ajax_report')
            ->assert('basket', '\d+')
        ;

        $controllers->post('/ajax/SET_NOTE/{sselcont_id}/', 'controller.lightbox:ajaxSetNoteAction')
            ->bind('lightbox_ajax_set_note')
            ->assert('sselcont_id', '\d+')
        ;

        $controllers->post('/ajax/SET_ELEMENT_AGREEMENT/{sselcont_id}/', 'controller.lightbox:ajaxSetElementAgreementAction')
            ->bind('lightbox_ajax_set_element_agreement')
            ->assert('sselcont_id', '\d+')
        ;

        $controllers->post('/ajax/SET_RELEASE/{basket}/', 'controller.lightbox:ajaxSetReleaseAction')
            ->bind('lightbox_ajax_set_release')
            ->assert('basket', '\d+')
        ;

        $controllers->get('/ajax/GET_ELEMENTS/{basket}/', 'controller.lightbox:ajaxGetElementsAction')
            ->bind('lightbox_ajax_get_elements')
            ->assert('basket', '\d+')
        ;

        return $controllers;
    }

    /**
     * @param Request            $request
     * @param PhraseaApplication $app
     * @return RedirectResponse|null
     */
    public function redirectOnLogRequests(Request $request, PhraseaApplication $app)
    {
        if (!$request->query->has('LOG')) {
            return null;
        }

        if ($app->getAuthenticator()->isAuthenticated()) {
            $app->getAuthenticator()->closeAccount();
        }

        if (null === $token = $app['repo.tokens']->findValidToken($request->query->get('LOG'))) {
            $app->addFlash('error', $app->trans('The URL you used is out of date, please login'));

            return $app->redirectPath('homepage');
        }

        /** @var Token $token */
        $app->getAuthenticator()->openAccount($token->getUser());

        switch ($token->getType()) {
            case TokenManipulator::TYPE_FEED_ENTRY:
                return $app->redirectPath('lightbox_feed_entry', ['entry_id' => $token->getData()]);
            case TokenManipulator::TYPE_VALIDATE:
            case TokenManipulator::TYPE_VIEW:
               return $app->redirectPath('lightbox_validation', ['basket' => $token->getData()]);
        }

        return null;
    }
}
