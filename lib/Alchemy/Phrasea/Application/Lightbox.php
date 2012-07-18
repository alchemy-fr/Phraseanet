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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Silex\Application as SilexApplication;
use Symfony\Component\HttpFoundation\Response;
use Alchemy\Phrasea\Controller\Exception as ControllerException;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(
        function() {

            $app = new PhraseaApplication();

            $app->get('/', function (SilexApplication $app) {
                    \User_Adapter::updateClientInfos((6));

                    $em = $app['phraseanet.core']->getEntityManager();
                    $repository = $em->getRepository('\Entities\Basket');

                    $current_user = $app['phraseanet.core']->getAuthenticatedUser();

                    /* @var $repository \Repositories\BasketRepository */

                    $basket_collection = array_merge(
                        $repository->findActiveByUser($current_user)
                        , $repository->findActiveValidationByUser($current_user)
                    );

                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();

                    $browser = \Browser::getInstance();

                    $template = 'lightbox/index.twig';
                    if ( ! $browser->isNewGeneration() && ! $browser->isMobile()) {
                        $template = 'lightbox/IE6/index.twig';
                    }

                    $output = $twig->render($template, array(
                        'baskets_collection' => $basket_collection,
                        'module_name'        => 'Lightbox',
                        'module'             => 'lightbox'
                        )
                    );
                    $response = new Response($output);
                    $response->setCharset('UTF-8');

                    return $response;
                }
            );

            $app->get('/ajax/NOTE_FORM/{sselcont_id}/', function(SilexApplication $app, $sselcont_id) {
                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();
                    $browser = \Browser::getInstance();

                    if ( ! $browser->isMobile()) {
                        return new Response('');
                    }

                    $em = $app['phraseanet.core']->getEntityManager();

                    /* @var $repository \Repositories\BasketElementRepository */
                    $repository = $em->getRepository('\Entities\BasketElement');

                    $basket_element = $repository->findUserElement($sselcont_id, $app['phraseanet.core']->getAuthenticatedUser());

                    $template = 'lightbox/note_form.twig';
                    $output = $twig->render($template, array('basket_element' => $basket_element, 'module_name'    => ''));

                    return new Response($output);
                }
            )->assert('sselcont_id', '\d+');

            $app->get('/ajax/LOAD_BASKET_ELEMENT/{sselcont_id}/', function(SilexApplication $app, $sselcont_id) {
                    $browser = \Browser::getInstance();

                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();

                    $em = $app['phraseanet.core']->getEntityManager();

                    /* @var $repository \Repositories\BasketElementRepository */
                    $repository = $em->getRepository('\Entities\BasketElement');

                    $BasketElement = $repository->findUserElement($sselcont_id, $app['phraseanet.core']->getAuthenticatedUser());

                    if ($browser->isMobile()) {
                        $output = $twig->render('lightbox/basket_element.twig', array(
                            'basket_element' => $BasketElement,
                            'module_name'    => $BasketElement->getRecord()->get_title()
                            )
                        );

                        return new Response($output);
                    } else {
                        $template_options = 'lightbox/sc_options_box.twig';
                        $template_agreement = 'lightbox/agreement_box.twig';
                        $template_selector = 'lightbox/selector_box.twig';
                        $template_note = 'lightbox/sc_note.twig';
                        $template_preview = 'common/preview.html';
                        $template_caption = 'common/caption.html';

                        if ( ! $browser->isNewGeneration()) {
                            $template_options = 'lightbox/IE6/sc_options_box.twig';
                            $template_agreement = 'lightbox/IE6/agreement_box.twig';
                        }

                        $Basket = $BasketElement->getBasket();

                        $ret = array();
                        $ret['number'] = $BasketElement->getRecord()->get_number();
                        $ret['title'] = $BasketElement->getRecord()->get_title();

                        $ret['preview'] = $twig->render($template_preview, array('record'             => $BasketElement->getRecord(), 'not_wrapped'        => true));
                        $ret['options_html'] = $twig->render($template_options, array('basket_element'       => $BasketElement));
                        $ret['agreement_html'] = $twig->render($template_agreement, array('basket'              => $Basket, 'basket_element'      => $BasketElement));
                        $ret['selector_html'] = $twig->render($template_selector, array('basket_element'  => $BasketElement));
                        $ret['note_html'] = $twig->render($template_note, array('basket_element' => $BasketElement));
                        $ret['caption'] = $twig->render($template_caption, array('view'   => 'preview', 'record' => $BasketElement->getRecord()));

                        $Serializer = $app['phraseanet.core']['Serializer'];

                        return new Response(
                                $Serializer->serialize($ret, 'json')
                                , 200
                                , array('Content-Type' => 'application/json')
                        );
                    }
                }
            )->assert('sselcont_id', '\d+');

            $app->get('/ajax/LOAD_FEED_ITEM/{entry_id}/{item_id}/', function(SilexApplication $app, $entry_id, $item_id) {
                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();

                    $entry = \Feed_Entry_Adapter::load_from_id($app['phraseanet.appbox'], $entry_id);
                    $item = new \Feed_Entry_Item($app['phraseanet.appbox'], $entry, $item_id);

                    $browser = \Browser::getInstance();

                    if ($browser->isMobile()) {
                        $output = $twig->render('lightbox/feed_element.twig', array(
                            'feed_element' => $item,
                            'module_name'  => $item->get_record()->get_title()
                            )
                        );

                        return new Response($output);
                    } else {
                        $template_options = 'lightbox/feed_options_box.twig';
                        $template_preview = 'common/preview.html';
                        $template_caption = 'common/caption.html';

                        if ( ! $browser->isNewGeneration()) {
                            $template_options = 'lightbox/IE6/feed_options_box.twig';
                        }

                        $ret = array();
                        $ret['number'] = $item->get_record()->get_number();
                        $ret['title'] = $item->get_record()->get_title();

                        $ret['preview'] = $twig->render($template_preview, array('record'             => $item->get_record(), 'not_wrapped'        => true));
                        $ret['options_html'] = $twig->render($template_options, array('feed_element'  => $item));
                        $ret['caption'] = $twig->render($template_caption, array('view'   => 'preview', 'record' => $item->get_record()));

                        $ret['agreement_html'] = $ret['selector_html'] = $ret['note_html'] = '';

                        $Serializer = $app['phraseanet.core']['Serializer'];

                        return new Response(
                                $Serializer->serialize($ret, 'json')
                                , 200
                                , array('Content-type' => 'application/json')
                        );
                    }
                }
            )->assert('entry_id', '\d+')->assert('item_id', '\d+');

            $app->get('/validate/{ssel_id}/', function (SilexApplication $app, $ssel_id) {

                    \User_Adapter::updateClientInfos((6));

                    $browser = \Browser::getInstance();

                    $em = $app['phraseanet.core']->getEntityManager();
                    $repository = $em->getRepository('\Entities\Basket');

                    /* @var $repository \Repositories\BasketRepository */
                    $basket_collection = $repository->findActiveValidationAndBasketByUser(
                        $app['phraseanet.core']->getAuthenticatedUser()
                    );

                    $basket = $repository->findUserBasket(
                        $ssel_id
                        , $app['phraseanet.core']->getAuthenticatedUser()
                        , false
                    );

                    if ($basket->getIsRead() === false) {
                        $basket = $em->merge($basket);
                        $basket->setIsRead(true);
                        $em->flush();
                    }

                    if ($basket->getValidation() && $basket->getValidation()->getParticipant($app['phraseanet.core']->getAuthenticatedUser())->getIsAware() === false) {
                        $basket = $em->merge($basket);
                        $basket->getValidation()->getParticipant($app['phraseanet.core']->getAuthenticatedUser())->setIsAware(true);
                        $em->flush();
                    }

                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();

                    $template = 'lightbox/validate.twig';

                    if ( ! $browser->isNewGeneration() && ! $browser->isMobile())
                        $template = 'lightbox/IE6/validate.twig';

                    $response = new Response($twig->render($template, array(
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

            $app->get('/compare/{ssel_id}/', function (SilexApplication $app, $ssel_id) {

                    \User_Adapter::updateClientInfos((6));

                    $browser = \Browser::getInstance();

                    $em = $app['phraseanet.core']->getEntityManager();
                    $repository = $em->getRepository('\Entities\Basket');

                    /* @var $repository \Repositories\BasketRepository */
                    $basket_collection = $repository->findActiveValidationAndBasketByUser(
                        $app['phraseanet.core']->getAuthenticatedUser()
                    );

                    $basket = $repository->findUserBasket(
                        $ssel_id
                        , $app['phraseanet.core']->getAuthenticatedUser()
                        , false
                    );

                    if ($basket->getIsRead() === false) {
                        $basket = $em->merge($basket);
                        $basket->setIsRead(true);
                        $em->flush();
                    }

                    if ($basket->getValidation() && $basket->getValidation()->getParticipant($app['phraseanet.core']->getAuthenticatedUser())->getIsAware() === false) {
                        $basket = $em->merge($basket);
                        $basket->getValidation()->getParticipant($app['phraseanet.core']->getAuthenticatedUser())->setIsAware(true);
                        $em->flush();
                    }

                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();

                    $template = 'lightbox/validate.twig';

                    if ( ! $browser->isNewGeneration() && ! $browser->isMobile())
                        $template = 'lightbox/IE6/validate.twig';

                    $response = new Response($twig->render($template, array(
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

            $app->get('/feeds/entry/{entry_id}/', function (SilexApplication $app, $entry_id) {

                    \User_Adapter::updateClientInfos((6));

                    $browser = \Browser::getInstance();

                    $feed_entry = \Feed_Entry_Adapter::load_from_id($app['phraseanet.appbox'], $entry_id);

                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();

                    $template = 'lightbox/feed.twig';

                    if ( ! $browser->isNewGeneration() && ! $browser->isMobile())
                        $template = 'lightbox/IE6/feed.twig';

                    $output = $twig->render($template, array(
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

            $app->get('/ajax/LOAD_REPORT/{ssel_id}/', function(SilexApplication $app, $ssel_id) {
                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();

                    $browser = \Browser::getInstance();

                    $template = 'lightbox/basket_content_report.twig';

                    $em = $app['phraseanet.core']->getEntityManager();
                    $repository = $em->getRepository('\Entities\Basket');

                    /* @var $repository \Repositories\BasketRepository */
                    $basket = $repository->findUserBasket(
                        $ssel_id
                        , $app['phraseanet.core']->getAuthenticatedUser()
                        , false
                    );

                    $response = new Response($twig->render($template, array('basket' => $basket)));
                    $response->setCharset('UTF-8');

                    return $response;
                }
            )->assert('ssel_id', '\d+');

            $app->post('/ajax/SET_NOTE/{sselcont_id}/', function (SilexApplication $app, $sselcont_id) {
                    $output = array('error' => true, 'datas' => _('Erreur lors de l\'enregistrement des donnees'));

                    $request = $app['request'];
                    $note = $request->get('note');

                    if (is_null($note)) {
                        Return new Response('You must provide a note value', 400);
                    }

                    $em = $app['phraseanet.core']->getEntityManager();

                    /* @var $repository \Repositories\BasketElementRepository */
                    $repository = $em->getRepository('\Entities\BasketElement');

                    $basket_element = $repository->findUserElement($sselcont_id, $app['phraseanet.core']->getAuthenticatedUser());

                    $validationDatas = $basket_element->getUserValidationDatas($app['phraseanet.core']->getAuthenticatedUser());

                    $validationDatas->setNote($note);

                    $em->merge($validationDatas);

                    $em->flush();

                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();

                    $browser = \Browser::getInstance();

                    if ($browser->isMobile()) {
                        $datas = $twig->render('lightbox/sc_note.twig', array('basket_element' => $basket_element));

                        $output = array('error' => false, 'datas' => $datas);
                    } else {
                        $template = 'lightbox/sc_note.twig';

                        $datas = $twig->render($template, array('basket_element' => $basket_element));

                        $output = array('error' => false, 'datas' => $datas);
                    }

                    $Serializer = $app['phraseanet.core']['Serializer'];

                    return new Response(
                            $Serializer->serialize($output, 'json')
                            , 200
                            , array('Content-Type' => 'application/json')
                    );
                }
            )->assert('sselcont_id', '\d+');

            $app->post('/ajax/SET_ELEMENT_AGREEMENT/{sselcont_id}/', function(SilexApplication $app, $sselcont_id) {
                    $request = $app['request'];
                    $agreement = $request->get('agreement');

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

                        $user = $app['phraseanet.core']->getAuthenticatedUser();
                        $em = $app['phraseanet.core']->getEntityManager();
                        $repository = $em->getRepository('\Entities\BasketElement');

                        /* @var $repository \Repositories\BasketElementRepository */
                        $basket_element = $repository->findUserElement(
                            $sselcont_id
                            , $user
                        );
                        /* @var $basket_element \Entities\BasketElement */
                        $validationDatas = $basket_element->getUserValidationDatas($user);

                        if ( ! $basket_element->getBasket()
                                ->getValidation()
                                ->getParticipant($user)->getCanAgree()) {
                            throw new ControllerException('You can not agree on this');
                        }

                        $validationDatas->setAgreement($agreement);

                        $participant = $basket_element->getBasket()
                            ->getValidation()
                            ->getParticipant($user);

                        $em->merge($basket_element);

                        $em->flush();

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
                    $Serializer = $app['phraseanet.core']['Serializer'];

                    return new Response(
                            $Serializer->serialize($ret, 'json')
                            , 200
                            , array('Content-Type' => 'application/json')
                    );
                }
            )->assert('sselcont_id', '\d+');

            $app->post('/ajax/SET_RELEASE/{ssel_id}/', function(SilexApplication $app, $ssel_id) {

                    $em = $app['phraseanet.core']->getEntityManager();

                    $user = $app['phraseanet.core']->getAuthenticatedUser();

                    $repository = $em->getRepository('\Entities\Basket');

                    $datas = array('error' => true, 'datas' => '');

                    try {
                        /* @var $repository \Repositories\BasketRepository */
                        $basket = $repository->findUserBasket(
                            $ssel_id
                            , $user
                            , false
                        );

                        if ( ! $basket->getValidation()) {
                            throw new ControllerException('There is no validation session attached to this basket');
                        }

                        if ( ! $basket->getValidation()->getParticipant($user)->getCanAgree()) {
                            throw new ControllerException('You have not right to agree');
                        }

                        /* @var $basket \Entities\Basket */
                        $participant = $basket->getValidation()->getParticipant($user);

                        $evt_mngr = \eventsmanager_broker::getInstance($app['phraseanet.appbox'], $app['phraseanet.core']);

                        $expires = new \DateTime('+10 days');
                        $url = $app['phraseanet.appbox']->get_registry()->get('GV_ServerName')
                            . 'lightbox/index.php?LOG=' . \random::getUrlToken(
                                \random::TYPE_VALIDATE
                                , $basket->getValidation()->getInitiator()->get_id()
                                , $expires
                                , $basket->getId()
                        );

                        $to = $basket->getValidation()->getInitiator()->get_id();
                        $params = array(
                            'ssel_id' => $basket->getId(),
                            'from'    => $app['phraseanet.core']->getAuthenticatedUser()->get_id(),
                            'url'     => $url,
                            'to'      => $to
                        );

                        $evt_mngr->trigger('__VALIDATION_DONE__', $params);

                        $participant->setIsConfirmed(true);

                        $em->merge($participant);

                        $em->flush();

                        $datas = array('error' => false, 'datas' => _('Envoie avec succes'));
                    } catch (ControllerException $e) {
                        $datas = array('error' => true, 'datas' => $e->getMessage());
                    }

                    $Serializer = $app['phraseanet.core']['Serializer'];

                    $response = new Response(
                            $Serializer->serialize($datas, 'json')
                            , 200
                            , array('Content-Type' => 'application/json')
                    );

                    $response->setCharset('UTF-8');

                    return $response;
                }
            )->assert('ssel_id', '\d+');

            $app->error(function($e) use($app) {

                    /* @var $twig \Twig_Environment */
                    $twig = $app['phraseanet.core']->getTwig();
                    $registry = \registry::get_instance();

                    $template = 'lightbox/error.twig';

                    if ($registry->get('GV_debug')) {
                        $options = array(
                            'module'      => 'validation',
                            'module_name' => _('admin::monitor: module validation'),
                            'error'       => sprintf(
                                '%s in %s on line %s '
                                , $e->getMessage()
                                , $e->getFile()
                                , $e->getLine()
                            )
                        );
                    } else {
                        $options = array(
                            'module'      => 'validation',
                            'module_name' => _('admin::monitor: module validation'),
                            'error'       => ''
                        );
                    }
                    $output = $twig->render($template, $options);
                    $response = new Response($output, 404);
                    $response->setCharset('UTF-8');

                    return $response;
                });

            return $app;
        }
);
