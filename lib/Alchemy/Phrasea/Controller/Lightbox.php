<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Exception\SessionNotFound;
use Alchemy\Phrasea\Controller\Exception as ControllerException;
use Silex\ControllerProviderInterface;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Lightbox implements ControllerProviderInterface
{
    public function connect(SilexApplication $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            if (!$request->query->has('LOG')) {
                return;
            }

            if ($app['authentication']->isAuthenticated()) {
                $app['authentication']->closeAccount();
            }

            if (false === $usr_id = $app['authentication.token-validator']->isValid($request->query->get('LOG'))) {
                $app->addFlash('error', _('The URL you used is out of date, please login'));

                return $app->redirectPath('homepage');
            }

            $app['authentication']->openAccount(\User_Adapter::getInstance($usr_id, $app));

            try {
                $datas = $app['tokens']->helloToken($request->query->get('LOG'));
            } catch (NotFoundHttpException $e) {
                return;
            }
            switch ($datas['type']) {
                case \random::TYPE_FEED_ENTRY:
                    return $app->redirectPath('lightbox_feed_entry', array('entry_id' => $datas['datas']));
                    break;
                case \random::TYPE_VALIDATE:
                case \random::TYPE_VIEW:
                    return $app->redirectPath('lightbox_validation', array('ssel_id' => $datas['datas']));
                    break;
            }
        });

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAuthentication();
        });

        $controllers->get('/', function (SilexApplication $app) {
            try {
                \Session_Logger::updateClientInfos($app, 6);
            } catch (SessionNotFound $e) {
                return $app->redirectPath('logout');
            }

            $repository = $app['EM']->getRepository('\Entities\Basket');

            /* @var $repository \Repositories\BasketRepository */

            $basket_collection = array_merge(
                $repository->findActiveByUser($app['authentication']->getUser())
                , $repository->findActiveValidationByUser($app['authentication']->getUser())
            );

            $template = 'lightbox/index.html.twig';
            if (!$app['browser']->isNewGeneration() && !$app['browser']->isMobile()) {
                $template = 'lightbox/IE6/index.html.twig';
            }

            return new Response($app['twig']->render($template, array(
                    'baskets_collection' => $basket_collection,
                    'module_name'        => 'Lightbox',
                    'module'             => 'lightbox'
                    )
            ));
        })
            ->bind('lightbox');

        $controllers->get('/ajax/NOTE_FORM/{sselcont_id}/', function(SilexApplication $app, $sselcont_id) {

            if (!$app['browser']->isMobile()) {
                return new Response('');
            }

            $basketElement = $app['EM']
                ->getRepository('\Entities\BasketElement')
                ->findUserElement($sselcont_id, $app['authentication']->getUser());

            $parameters = array(
                'basket_element' => $basketElement,
                'module_name'    => '',
            );

            return $app['twig']->render('lightbox/note_form.html.twig', $parameters);
        })
            ->bind('lightbox_ajax_note_form')
            ->assert('sselcont_id', '\d+');

        $controllers->get('/ajax/LOAD_BASKET_ELEMENT/{sselcont_id}/', function(SilexApplication $app, $sselcont_id) {
            /* @var $repository \Repositories\BasketElementRepository */
            $repository = $app['EM']->getRepository('\Entities\BasketElement');

            $BasketElement = $repository->findUserElement($sselcont_id, $app['authentication']->getUser());

            if ($app['browser']->isMobile()) {
                $output = $app['twig']->render('lightbox/basket_element.html.twig', array(
                    'basket_element' => $BasketElement,
                    'module_name'    => $BasketElement->getRecord($app)->get_title()
                    )
                );

                return new Response($output);
            } else {
                $template_options = 'lightbox/sc_options_box.html.twig';
                $template_agreement = 'lightbox/agreement_box.html.twig';
                $template_selector = 'lightbox/selector_box.html.twig';
                $template_note = 'lightbox/sc_note.html.twig';
                $template_preview = 'common/preview.html.twig';
                $template_caption = 'common/caption.html.twig';

                if (!$app['browser']->isNewGeneration()) {
                    $template_options = 'lightbox/IE6/sc_options_box.html.twig';
                    $template_agreement = 'lightbox/IE6/agreement_box.html.twig';
                }

                $Basket = $BasketElement->getBasket();

                $ret = array();
                $ret['number'] = $BasketElement->getRecord($app)->get_number();
                $ret['title'] = $BasketElement->getRecord($app)->get_title();

                $ret['preview'] = $app['twig']->render($template_preview, array('record'             => $BasketElement->getRecord($app), 'not_wrapped'        => true));
                $ret['options_html'] = $app['twig']->render($template_options, array('basket_element'       => $BasketElement));
                $ret['agreement_html'] = $app['twig']->render($template_agreement, array('basket'              => $Basket, 'basket_element'      => $BasketElement));
                $ret['selector_html'] = $app['twig']->render($template_selector, array('basket_element'  => $BasketElement));
                $ret['note_html'] = $app['twig']->render($template_note, array('basket_element' => $BasketElement));
                $ret['caption'] = $app['twig']->render($template_caption, array('view'   => 'preview', 'record' => $BasketElement->getRecord($app)));

                return $app->json($ret);
            }
        })
            ->bind('lightbox_ajax_load_basketelement')
            ->assert('sselcont_id', '\d+');

        $controllers->get('/ajax/LOAD_FEED_ITEM/{entry_id}/{item_id}/', function(SilexApplication $app, $entry_id, $item_id) {

            $entry = \Feed_Entry_Adapter::load_from_id($app, $entry_id);
            $item = new \Feed_Entry_Item($app['phraseanet.appbox'], $entry, $item_id);

            if ($app['browser']->isMobile()) {
                $output = $app['twig']->render('lightbox/feed_element.html.twig', array(
                    'feed_element' => $item,
                    'module_name'  => $item->get_record()->get_title()
                    )
                );

                return new Response($output);
            } else {
                $template_options = 'lightbox/feed_options_box.html.twig';
                $template_preview = 'common/preview.html.twig';
                $template_caption = 'common/caption.html.twig';

                if (!$app['browser']->isNewGeneration()) {
                    $template_options = 'lightbox/IE6/feed_options_box.html.twig';
                }

                $ret = array();
                $ret['number'] = $item->get_record()->get_number();
                $ret['title'] = $item->get_record()->get_title();

                $ret['preview'] = $app['twig']->render($template_preview, array('record'             => $item->get_record(), 'not_wrapped'        => true));
                $ret['options_html'] = $app['twig']->render($template_options, array('feed_element'  => $item));
                $ret['caption'] = $app['twig']->render($template_caption, array('view'   => 'preview', 'record' => $item->get_record()));

                $ret['agreement_html'] = $ret['selector_html'] = $ret['note_html'] = '';

                return $app->json($ret);
            }
        })
            ->bind('lightbox_ajax_load_feeditem')
            ->assert('entry_id', '\d+')
            ->assert('item_id', '\d+');

        $controllers->get('/validate/{ssel_id}/', function (SilexApplication $app, $ssel_id) {

            try {
                \Session_Logger::updateClientInfos($app, 6);
            } catch (SessionNotFound $e) {
                return $app->redirectPath('logout');
            }

            $repository = $app['EM']->getRepository('\Entities\Basket');

            /* @var $repository \Repositories\BasketRepository */
            $basket_collection = $repository->findActiveValidationAndBasketByUser(
                $app['authentication']->getUser()
            );

            $basket = $repository->findUserBasket(
                $app, $ssel_id
                , $app['authentication']->getUser()
                , false
            );

            if ($basket->getIsRead() === false) {
                $basket = $app['EM']->merge($basket);
                $basket->setIsRead(true);
                $app['EM']->flush();
            }

            if ($basket->getValidation() && $basket->getValidation()->getParticipant($app['authentication']->getUser(), $app)->getIsAware() === false) {
                $basket = $app['EM']->merge($basket);
                $basket->getValidation()->getParticipant($app['authentication']->getUser(), $app)->setIsAware(true);
                $app['EM']->flush();
            }

            $template = 'lightbox/validate.html.twig';

            if (!$app['browser']->isNewGeneration() && !$app['browser']->isMobile()) {
                $template = 'lightbox/IE6/validate.html.twig';
            }

            $response = new Response($app['twig']->render($template, array(
                        'baskets_collection' => $basket_collection,
                        'basket'             => $basket,
                        'local_title'        => strip_tags($basket->getName()),
                        'module'             => 'lightbox',
                        'module_name'        => _('admin::monitor: module validation')
                        )
                ));
            $response->setCharset('UTF-8');

            return $response;
        })
            ->bind('lightbox_validation')
            ->assert('ssel_id', '\d+');

        $controllers->get('/compare/{ssel_id}/', function (SilexApplication $app, $ssel_id) {

            try {
                \Session_Logger::updateClientInfos($app, 6);
            } catch (SessionNotFound $e) {
                return $app->redirectPath('logout');
            }

            $repository = $app['EM']->getRepository('\Entities\Basket');

            /* @var $repository \Repositories\BasketRepository */
            $basket_collection = $repository->findActiveValidationAndBasketByUser(
                $app['authentication']->getUser()
            );

            $basket = $repository->findUserBasket(
                $app, $ssel_id
                , $app['authentication']->getUser()
                , false
            );

            if ($basket->getIsRead() === false) {
                $basket = $app['EM']->merge($basket);
                $basket->setIsRead(true);
                $app['EM']->flush();
            }

            if ($basket->getValidation() && $basket->getValidation()->getParticipant($app['authentication']->getUser(), $app)->getIsAware() === false) {
                $basket = $app['EM']->merge($basket);
                $basket->getValidation()->getParticipant($app['authentication']->getUser(), $app)->setIsAware(true);
                $app['EM']->flush();
            }

            $template = 'lightbox/validate.html.twig';

            if (!$app['browser']->isNewGeneration() && !$app['browser']->isMobile()) {
                $template = 'lightbox/IE6/validate.html.twig';
            }

            $response = new Response($app['twig']->render($template, array(
                        'baskets_collection' => $basket_collection,
                        'basket'             => $basket,
                        'local_title'        => strip_tags($basket->getName()),
                        'module'             => 'lightbox',
                        'module_name'        => _('admin::monitor: module validation')
                        )
                ));
            $response->setCharset('UTF-8');

            return $response;
        })
            ->bind('lightbox_compare')
            ->assert('ssel_id', '\d+');

        $controllers->get('/feeds/entry/{entry_id}/', function (SilexApplication $app, $entry_id) {

            try {
                \Session_Logger::updateClientInfos($app, 6);
            } catch (SessionNotFound $e) {
                return $app->redirectPath('logout');
            }

            $feed_entry = \Feed_Entry_Adapter::load_from_id($app, $entry_id);

            $template = 'lightbox/feed.html.twig';

            if (!$app['browser']->isNewGeneration() && !$app['browser']->isMobile()) {
                $template = 'lightbox/IE6/feed.html.twig';
            }

            $content = $feed_entry->get_content();

            $output = $app['twig']->render($template, array(
                'feed_entry'  => $feed_entry,
                'first_item'  => array_shift($content),
                'local_title' => $feed_entry->get_title(),
                'module'      => 'lightbox',
                'module_name' => _('admin::monitor: module validation')
                )
            );
            $response = new Response($output, 200);
            $response->setCharset('UTF-8');

            return $response;
        })
            ->bind('lightbox_feed_entry')
            ->assert('entry_id', '\d+');

        $controllers->get('/ajax/LOAD_REPORT/{ssel_id}/', function(SilexApplication $app, $ssel_id) {

            $template = 'lightbox/basket_content_report.html.twig';

            $repository = $app['EM']->getRepository('\Entities\Basket');

            /* @var $repository \Repositories\BasketRepository */
            $basket = $repository->findUserBasket(
                $app, $ssel_id
                , $app['authentication']->getUser()
                , false
            );

            $response = new Response($app['twig']->render($template, array('basket' => $basket)));
            $response->setCharset('UTF-8');

            return $response;
        })
            ->bind('lightbox_ajax_report')
            ->assert('ssel_id', '\d+');

        $controllers->post('/ajax/SET_NOTE/{sselcont_id}/', function (SilexApplication $app, $sselcont_id) {
            $output = array('error' => true, 'datas' => _('Erreur lors de l\'enregistrement des donnees'));

            $request = $app['request'];
            $note = $request->request->get('note');

            if (is_null($note)) {
                Return new Response('You must provide a note value', 400);
            }

            /* @var $repository \Repositories\BasketElementRepository */
            $repository = $app['EM']->getRepository('\Entities\BasketElement');

            $basket_element = $repository->findUserElement($sselcont_id, $app['authentication']->getUser());

            $validationDatas = $basket_element->getUserValidationDatas($app['authentication']->getUser(), $app);

            $validationDatas->setNote($note);

            $app['EM']->merge($validationDatas);

            $app['EM']->flush();

            if ($app['browser']->isMobile()) {
                $datas = $app['twig']->render('lightbox/sc_note.html.twig', array('basket_element' => $basket_element));

                $output = array('error' => false, 'datas' => $datas);
            } else {
                $template = 'lightbox/sc_note.html.twig';

                $datas = $app['twig']->render($template, array('basket_element' => $basket_element));

                $output = array('error' => false, 'datas' => $datas);
            }

            return $app->json($output);
        })
            ->bind('lightbox_ajax_set_note')
            ->assert('sselcont_id', '\d+');

        $controllers->post('/ajax/SET_ELEMENT_AGREEMENT/{sselcont_id}/', function(SilexApplication $app, $sselcont_id) {
            $request = $app['request'];
            $agreement = $request->request->get('agreement');

            if (is_null($agreement)) {
                Return new Response('You must provide an agreement value', 400);
            }

            $agreement = $agreement > 0;

            $releasable = false;
            try {
                $ret = array(
                    'error'      => true,
                    'releasable' => false,
                    'datas'      => _('Erreur lors de la mise a jour des donnes ')
                );

                $repository = $app['EM']->getRepository('\Entities\BasketElement');

                /* @var $repository \Repositories\BasketElementRepository */
                $basket_element = $repository->findUserElement(
                    $sselcont_id
                    , $app['authentication']->getUser()
                );
                /* @var $basket_element \Entities\BasketElement */
                $validationDatas = $basket_element->getUserValidationDatas($app['authentication']->getUser(), $app);

                if (!$basket_element->getBasket()
                        ->getValidation()
                        ->getParticipant($app['authentication']->getUser(), $app)->getCanAgree()) {
                    throw new ControllerException('You can not agree on this');
                }

                $validationDatas->setAgreement($agreement);

                $participant = $basket_element->getBasket()
                    ->getValidation()
                    ->getParticipant($app['authentication']->getUser(), $app);

                $app['EM']->merge($basket_element);

                $app['EM']->flush();

                $releasable = false;
                if ($participant->isReleasable() === true) {
                    $releasable = _('Do you want to send your report ?');
                }

                $ret = array(
                    'error'      => false
                    , 'datas'      => ''
                    , 'releasable' => $releasable
                );
            } catch (ControllerException $e) {
                $ret['datas'] = $e->getMessage();
            }

            return $app->json($ret);
        })
            ->bind('lightbox_ajax_set_element_agreement')
            ->assert('sselcont_id', '\d+');

        $controllers->post('/ajax/SET_RELEASE/{ssel_id}/', function(SilexApplication $app, $ssel_id) {

            $repository = $app['EM']->getRepository('\Entities\Basket');

            $datas = array('error' => true, 'datas' => '');

            try {
                /* @var $repository \Repositories\BasketRepository */
                $basket = $repository->findUserBasket(
                    $app, $ssel_id
                    , $app['authentication']->getUser()
                    , false
                );

                if (!$basket->getValidation()) {
                    throw new ControllerException('There is no validation session attached to this basket');
                }

                if (!$basket->getValidation()->getParticipant($app['authentication']->getUser(), $app)->getCanAgree()) {
                    throw new ControllerException('You have not right to agree');
                }

                $agreed = false;
                /* @var $basket \Entities\Basket */
                foreach ($basket->getElements() as $element) {
                    if (null !== $element->getUserValidationDatas($app['authentication']->getUser(), $app)->getAgreement()) {
                        $agreed = true;
                    }
                }

                if (!$agreed) {
                    throw new ControllerException(_('You have to give your feedback at least on one document to send a report'));
                }

                /* @var $basket \Entities\Basket */
                $participant = $basket->getValidation()->getParticipant($app['authentication']->getUser(), $app);

                $expires = new \DateTime('+10 days');
                $url = $app->url('lightbox', array('LOG' => $app['tokens']->getUrlToken(
                        \random::TYPE_VALIDATE
                        , $basket->getValidation()->getInitiator($app)->get_id()
                        , $expires
                        , $basket->getId()
                )));

                $to = $basket->getValidation()->getInitiator($app)->get_id();
                $params = array(
                    'ssel_id' => $basket->getId(),
                    'from'    => $app['authentication']->getUser()->get_id(),
                    'url'     => $url,
                    'to'      => $to
                );

                $app['events-manager']->trigger('__VALIDATION_DONE__', $params);

                $participant->setIsConfirmed(true);

                $app['EM']->merge($participant);
                $app['EM']->flush();

                $datas = array('error' => false, 'datas' => _('Envoie avec succes'));
            } catch (ControllerException $e) {
                $datas = array('error' => true, 'datas' => $e->getMessage());
            }

            return $app->json($datas);
        })
            ->bind('lightbox_ajax_set_release')
            ->assert('ssel_id', '\d+');

        return $controllers;
    }
}
