<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Silex\ControllerProviderInterface;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Response;
use Alchemy\Phrasea\Controller\Exception as ControllerException;

class Lightbox implements ControllerProviderInterface
{

    public function connect(SilexApplication $app)
    {
        $controllers = $app['controllers_factory'];


        $controllers->get('/', function (SilexApplication $app) {
                \User_Adapter::updateClientInfos($app, 6);

                $repository = $app['EM']->getRepository('\Entities\Basket');

                $current_user = $app['phraseanet.user'];

                /* @var $repository \Repositories\BasketRepository */

                $basket_collection = array_merge(
                    $repository->findActiveByUser($app['phraseanet.user'])
                    , $repository->findActiveValidationByUser($current_user)
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
            }
        );

        $controllers->get('/ajax/NOTE_FORM/{sselcont_id}/', function(SilexApplication $app, $sselcont_id) {

                if (!$app['browser']->isMobile()) {
                    return new Response('');
                }

                $basketElement = $app['EM']
                    ->getRepository('\Entities\BasketElement')
                    ->findUserElement($sselcont_id, $app['phraseanet.user']);

                $parameters = array(
                    'basket_element' => $basketElement,
                    'module_name'    => '',
                );

                return $app['twig']->render('lightbox/note_form.html.twig', $parameters);
            }
        )->assert('sselcont_id', '\d+');

        $controllers->get('/ajax/LOAD_BASKET_ELEMENT/{sselcont_id}/', function(SilexApplication $app, $sselcont_id) {
                /* @var $repository \Repositories\BasketElementRepository */
                $repository = $app['EM']->getRepository('\Entities\BasketElement');

                $BasketElement = $repository->findUserElement($sselcont_id, $app['phraseanet.user']);

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
            }
        )->assert('sselcont_id', '\d+');

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
            }
        )->assert('entry_id', '\d+')->assert('item_id', '\d+');

        $controllers->get('/validate/{ssel_id}/', function (SilexApplication $app, $ssel_id) {

                \User_Adapter::updateClientInfos($app, 6);

                $repository = $app['EM']->getRepository('\Entities\Basket');

                /* @var $repository \Repositories\BasketRepository */
                $basket_collection = $repository->findActiveValidationAndBasketByUser(
                    $app['phraseanet.user']
                );

                $basket = $repository->findUserBasket(
                    $app, $ssel_id
                    , $app['phraseanet.user']
                    , false
                );

                if ($basket->getIsRead() === false) {
                    $basket = $app['EM']->merge($basket);
                    $basket->setIsRead(true);
                    $app['EM']->flush();
                }

                if ($basket->getValidation() && $basket->getValidation()->getParticipant($app['phraseanet.user'], $app)->getIsAware() === false) {
                    $basket = $app['EM']->merge($basket);
                    $basket->getValidation()->getParticipant($app['phraseanet.user'], $app)->setIsAware(true);
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
            }
        )->assert('ssel_id', '\d+');

        $controllers->get('/compare/{ssel_id}/', function (SilexApplication $app, $ssel_id) {

                \User_Adapter::updateClientInfos($app, 6);

                $repository = $app['EM']->getRepository('\Entities\Basket');

                /* @var $repository \Repositories\BasketRepository */
                $basket_collection = $repository->findActiveValidationAndBasketByUser(
                    $app['phraseanet.user']
                );

                $basket = $repository->findUserBasket(
                    $app, $ssel_id
                    , $app['phraseanet.user']
                    , false
                );

                if ($basket->getIsRead() === false) {
                    $basket = $app['EM']->merge($basket);
                    $basket->setIsRead(true);
                    $app['EM']->flush();
                }

                if ($basket->getValidation() && $basket->getValidation()->getParticipant($app['phraseanet.user'])->getIsAware() === false) {
                    $basket = $app['EM']->merge($basket);
                    $basket->getValidation()->getParticipant($app['phraseanet.user'], $app)->setIsAware(true);
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
            }
        )->assert('ssel_id', '\d+');

        $controllers->get('/feeds/entry/{entry_id}/', function (SilexApplication $app, $entry_id) {

                \User_Adapter::updateClientInfos($app, 6);

                $feed_entry = \Feed_Entry_Adapter::load_from_id($app, $entry_id);

                $template = 'lightbox/feed.html.twig';

                if (!$app['browser']->isNewGeneration() && !$app['browser']->isMobile()) {
                    $template = 'lightbox/IE6/feed.html.twig';
                }

                $output = $app['twig']->render($template, array(
                    'feed_entry'  => $feed_entry,
                    'first_item'  => array_shift($feed_entry->get_content()),
                    'local_title' => $feed_entry->get_title(),
                    'module'      => 'lightbox',
                    'module_name' => _('admin::monitor: module validation')
                    )
                );
                $response = new Response($output, 200);
                $response->setCharset('UTF-8');

                return $response;
            }
        )->assert('entry_id', '\d+');

        $controllers->get('/ajax/LOAD_REPORT/{ssel_id}/', function(SilexApplication $app, $ssel_id) {

                $template = 'lightbox/basket_content_report.html.twig';

                $repository = $app['EM']->getRepository('\Entities\Basket');

                /* @var $repository \Repositories\BasketRepository */
                $basket = $repository->findUserBasket(
                    $app, $ssel_id
                    , $app['phraseanet.user']
                    , false
                );

                $response = new Response($app['twig']->render($template, array('basket' => $basket)));
                $response->setCharset('UTF-8');

                return $response;
            }
        )->assert('ssel_id', '\d+');

        $controllers->post('/ajax/SET_NOTE/{sselcont_id}/', function (SilexApplication $app, $sselcont_id) {
                $output = array('error' => true, 'datas' => _('Erreur lors de l\'enregistrement des donnees'));

                $request = $app['request'];
                $note = $request->request->get('note');

                if (is_null($note)) {
                    Return new Response('You must provide a note value', 400);
                }

                /* @var $repository \Repositories\BasketElementRepository */
                $repository = $app['EM']->getRepository('\Entities\BasketElement');

                $basket_element = $repository->findUserElement($sselcont_id, $app['phraseanet.user']);

                $validationDatas = $basket_element->getUserValidationDatas($app['phraseanet.user'], $app);

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
            }
        )->assert('sselcont_id', '\d+');

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

                    $user = $app['phraseanet.user'];
                    $repository = $app['EM']->getRepository('\Entities\BasketElement');

                    /* @var $repository \Repositories\BasketElementRepository */
                    $basket_element = $repository->findUserElement(
                        $sselcont_id
                        , $user
                    );
                    /* @var $basket_element \Entities\BasketElement */
                    $validationDatas = $basket_element->getUserValidationDatas($user, $app);

                    if (!$basket_element->getBasket()
                            ->getValidation()
                            ->getParticipant($user, $app)->getCanAgree()) {
                        throw new ControllerException('You can not agree on this');
                    }

                    $validationDatas->setAgreement($agreement);

                    $participant = $basket_element->getBasket()
                        ->getValidation()
                        ->getParticipant($user, $app);

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
            }
        )->assert('sselcont_id', '\d+');

        $controllers->post('/ajax/SET_RELEASE/{ssel_id}/', function(SilexApplication $app, $ssel_id) {

                $user = $app['phraseanet.user'];

                $repository = $app['EM']->getRepository('\Entities\Basket');

                $datas = array('error' => true, 'datas' => '');

                try {
                    /* @var $repository \Repositories\BasketRepository */
                    $basket = $repository->findUserBasket(
                        $app, $ssel_id
                        , $user
                        , false
                    );

                    if (!$basket->getValidation()) {
                        throw new ControllerException('There is no validation session attached to this basket');
                    }

                    if (!$basket->getValidation()->getParticipant($user, $app)->getCanAgree()) {
                        throw new ControllerException('You have not right to agree');
                    }

                    /* @var $basket \Entities\Basket */
                    $participant = $basket->getValidation()->getParticipant($user, $app);

                    $evt_mngr = \eventsmanager_broker::getInstance($app);

                    $expires = new \DateTime('+10 days');
                    $url = $app['phraseanet.appbox']->get_registry()->get('GV_ServerName')
                        . 'lightbox/index.php?LOG=' . \random::getUrlToken(
                            $app, \random::TYPE_VALIDATE
                            , $basket->getValidation()->getInitiator($app)->get_id()
                            , $expires
                            , $basket->getId()
                    );

                    $to = $basket->getValidation()->getInitiator($app)->get_id();
                    $params = array(
                        'ssel_id' => $basket->getId(),
                        'from'    => $app['phraseanet.user']->get_id(),
                        'url'     => $url,
                        'to'      => $to
                    );

                    $evt_mngr->trigger('__VALIDATION_DONE__', $params);

                    $participant->setIsConfirmed(true);

                    $app['EM']->merge($participant);
                    $app['EM']->flush();

                    $datas = array('error' => false, 'datas' => _('Envoie avec succes'));
                } catch (ControllerException $e) {
                    $datas = array('error' => true, 'datas' => $e->getMessage());
                }

                return $app->json($datas);
            }
        )->assert('ssel_id', '\d+');

        $app->error(function($e) use($app) {

                $registry = $app['phraseanet.registry'];

                $template = 'lightbox/error.html.twig';

                if ($e instanceof Exception_Feed_EntryNotFound) {
                    $message = _('Feed entry not found');
                } else {
                    $message = _('Le panier demande nexiste plus');
                }


                if ($registry->get('GV_debug')) {
                    $options = array(
                        'module'      => 'validation',
                        'module_name' => _('admin::monitor: module validation'),
                        'error'       => sprintf(
                            '%s in %s on line %s '
                            , $e->getMessage()
                            , $e->getFile()
                            , $e->getLine()
                        ),
                        'message'     => $message,
                    );
                } else {
                    $options = array(
                        'module'      => 'validation',
                        'module_name' => _('admin::monitor: module validation'),
                        'error'       => '',
                        'message'     => $message,
                    );
                }
                $output = $app['twig']->render($template, $options);
                $response = new Response($output, 404, array('X-Status-Code' => 404));
                $response->setCharset('UTF-8');

                return $response;
            });

        return $controllers;
    }
}
