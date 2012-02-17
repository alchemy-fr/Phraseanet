<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\Controller\Exception as ControllerException;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(
    function()
    {
      $appbox = \appbox::get_instance();

      $session = $appbox->get_session();

      $app = new \Silex\Application();

      $app['Core'] = \bootstrap::getCore();

      $app["debug"] = $app["Core"]->getConfiguration()->isDebug();

      $app->get('/', function (\Silex\Application $app) use ($session, $appbox)
        {
          \User_Adapter::updateClientInfos((6));

          $em         = $app['Core']->getEntityManager();
          $repository = $em->getRepository('\Entities\Basket');

          /* @var $repository \Repositories\BasketRepository */
          $basket_collection = $repository->findActiveByUser(
            $app['Core']->getAuthenticatedUser()
          );
          /* @var $twig \Twig_Environment */
          $twig              = $app['Core']->getTwig();

          $browser = \Browser::getInstance();

          $template = 'lightbox/index.twig';
          if (!$browser->isNewGeneration() && !$browser->isMobile())
          {
            $template = 'lightbox/IE6/index.twig';
          }

          $output = $twig->render($template, array(
            'baskets_collection' => $basket_collection,
            'module_name'        => 'Lightbox',
            'module'             => 'lightbox'
            )
          );
          $response            = new Response($output);
          $response->setCharset('UTF-8');

          return $response;
        }
      );

      $app->get('/ajax/NOTE_FORM/{sselcont_id}/', function(\Silex\Application $app, $sselcont_id) use ($session, $appbox)
        {
          /* @var $twig \Twig_Environment */
          $twig    = $app['Core']->getTwig();
          $browser = \Browser::getInstance();

          if (!$browser->isMobile())

            return new Response('');


          $em = $app['Core']->getEntityManager();

          /* @var $repository \Repositories\BasketElementRepository */
          $repository = $em->getRepository('\Entities\BasketElement');

          $basket_element = $repository->findUserElement($sselcont_id, $app['Core']->getAuthenticatedUser());

          $template = 'lightbox/note_form.twig';
          $output   = $twig->render($template, array('basket_element' => $basket_element, 'module_name'    => ''));

          return new Response($output);
        }
      )->assert('sselcont_id', '\d+');

      $app->get('/ajax/LOAD_BASKET_ELEMENT/{sselcont_id}/', function(\Silex\Application $app, $sselcont_id)
        {
          $browser = \Browser::getInstance();

          /* @var $twig \Twig_Environment */
          $twig = $app['Core']->getTwig();

          $em = $app['Core']->getEntityManager();

          /* @var $repository \Repositories\BasketElementRepository */
          $repository = $em->getRepository('\Entities\BasketElement');

          $BasketElement = $repository->findUserElement($sselcont_id, $app['Core']->getAuthenticatedUser());

          if ($browser->isMobile())
          {
            $output = $twig->render('lightbox/basket_element.twig', array(
              'basket_element' => $BasketElement,
              'module_name'    => $BasketElement->getRecord()->get_title()
              )
            );

            return new Response($output);
          }
          else
          {
            $template_options   = 'lightbox/sc_options_box.twig';
            $template_agreement = 'lightbox/agreement_box.twig';
            $template_selector  = 'lightbox/selector_box.twig';
            $template_note      = 'lightbox/sc_note.twig';
            $template_preview   = 'common/preview.html';
            $template_caption   = 'common/caption.html';

            if (!$browser->isNewGeneration())
            {
              $template_options   = 'lightbox/IE6/sc_options_box.twig';
              $template_agreement = 'lightbox/IE6/agreement_box.twig';
            }
            $appbox             = \appbox::get_instance();
            $usr_id             = $appbox->get_session()->get_usr_id();


            $Basket = $BasketElement->getBasket();

            $ret = array();
            $ret['number'] = $BasketElement->getRecord()->get_number();
            $ret['title']  = $BasketElement->getRecord()->get_title();

            $ret['preview'] = $twig->render($template_preview, array('record'             => $BasketElement->getRecord(), 'not_wrapped'        => true));
            $ret['options_html'] = $twig->render($template_options, array('basket_element'       => $BasketElement));
            $ret['agreement_html'] = $twig->render($template_agreement, array('basket'              => $Basket, 'basket_element'      => $BasketElement));
            $ret['selector_html'] = $twig->render($template_selector, array('basket_element'  => $BasketElement));
            $ret['note_html'] = $twig->render($template_note, array('basket_element' => $BasketElement));
            $ret['caption']  = $twig->render($template_caption, array('view'   => 'preview', 'record' => $BasketElement->getRecord()));

            $Serializer = $app['Core']['Serializer'];

            return new Response(
                $Serializer->serialize($ret, 'json')
                , 200
                , array('Content-Type' => 'application/json')
            );
          }
        }
      )->assert('sselcont_id', '\d+');




      $app->get('/ajax/LOAD_FEED_ITEM/{entry_id}/{item_id}/', function(\Silex\Application $app, $entry_id, $item_id)
        {
          /* @var $twig \Twig_Environment */
          $twig = $app['Core']->getTwig();

          $appbox = \appbox::get_instance();
          $entry  = \Feed_Entry_Adapter::load_from_id($appbox, $entry_id);
          $item   = new \Feed_Entry_Item($appbox, $entry, $item_id);

          $browser = \Browser::getInstance();

          if ($browser->isMobile())
          {
            $output = $twig->render('lightbox/feed_element.twig', array(
              'feed_element' => $item,
              'module_name'  => $item->get_record()->get_title()
              )
            );

            return new Response($output);
          }
          else
          {
            $template_options = 'lightbox/feed_options_box.twig';
            $template_preview = 'common/preview.html';
            $template_caption = 'common/caption.html';

            if (!$browser->isNewGeneration())
            {
              $template_options = 'lightbox/IE6/feed_options_box.twig';
            }
            $usr_id           = $appbox->get_session()->get_usr_id();

            $ret = array();
            $ret['number'] = $item->get_record()->get_number();
            $ret['title']  = $item->get_record()->get_title();

            $ret['preview'] = $twig->render($template_preview, array('record'             => $item->get_record(), 'not_wrapped'        => true));
            $ret['options_html'] = $twig->render($template_options, array('feed_element'  => $item));
            $ret['caption'] = $twig->render($template_caption, array('view'   => 'preview', 'record' => $item->get_record()));


            $ret['agreement_html'] = $ret['selector_html']  = $ret['note_html']      = '';

            $Serializer = $app['Core']['Serializer'];

            return new Response(
                $Serializer->serialize($ret, 'json')
                , 200
                , array('Content-type' => 'application/json')
            );
          }
        }
      )->assert('entry_id', '\d+')->assert('item_id', '\d+');

      $app->get('/validate/{ssel_id}/', function (\Silex\Application $app, $ssel_id) use ($session, $appbox)
        {

          \User_Adapter::updateClientInfos((6));

          $browser = \Browser::getInstance();

          $em         = $app['Core']->getEntityManager();
          $repository = $em->getRepository('\Entities\Basket');

          /* @var $repository \Repositories\BasketRepository */
          $basket_collection = $repository->findActiveByUser(
            $app['Core']->getAuthenticatedUser()
          );

          $basket = $repository->findUserBasket(
            $ssel_id
            , $app['Core']->getAuthenticatedUser()
            , false
          );

          /* @var $twig \Twig_Environment */
          $twig = $app['Core']->getTwig();

          $template = 'lightbox/validate.twig';

          if (!$browser->isNewGeneration() && !$browser->isMobile())
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

      $app->get('/compare/{ssel_id}/', function (\Silex\Application $app, $ssel_id) use ($session, $appbox)
        {

          \User_Adapter::updateClientInfos((6));

          $browser = \Browser::getInstance();

          $em         = $app['Core']->getEntityManager();
          $repository = $em->getRepository('\Entities\Basket');

          /* @var $repository \Repositories\BasketRepository */
          $basket_collection = $repository->findActiveByUser(
            $app['Core']->getAuthenticatedUser()
          );

          $basket = $repository->findUserBasket(
            $ssel_id
            , $app['Core']->getAuthenticatedUser()
            , false
          );

          /* @var $twig \Twig_Environment */
          $twig = $app['Core']->getTwig();

          $template = 'lightbox/validate.twig';

          if (!$browser->isNewGeneration() && !$browser->isMobile())
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



      $app->get('/feeds/entry/{entry_id}/', function (\Silex\Application $app, $entry_id) use ($session, $appbox)
        {

          \User_Adapter::updateClientInfos((6));

          $browser = \Browser::getInstance();

          $feed_entry = \Feed_Entry_Adapter::load_from_id($appbox, $entry_id);

          /* @var $twig \Twig_Environment */
          $twig = $app['Core']->getTwig();

          $template = 'lightbox/feed.twig';

          if (!$browser->isNewGeneration() && !$browser->isMobile())
            $template = 'lightbox/IE6/feed.twig';

          $output = $twig->render($template, array(
            'feed_entry'  => $feed_entry,
            'first_item'  => array_shift($feed_entry->get_content()),
            'local_title' => $feed_entry->get_title(),
            'module'      => 'lightbox',
            'module_name' => _('admin::monitor: module validation')
            )
          );
          $response     = new Response($output, 200);
          $response->setCharset('UTF-8');

          return $response;
        }
      )->assert('entry_id', '\d+');

      $app->get('/ajax/LOAD_REPORT/{ssel_id}/', function(\Silex\Application $app, $ssel_id)
        {
          /* @var $twig \Twig_Environment */
          $twig = $app['Core']->getTwig();

          $browser = \Browser::getInstance();

          $template = 'lightbox/basket_content_report.twig';

          $em         = $app['Core']->getEntityManager();
          $repository = $em->getRepository('\Entities\Basket');

          /* @var $repository \Repositories\BasketRepository */
          $basket = $repository->findUserBasket(
            $ssel_id
            , $app['Core']->getAuthenticatedUser()
            , false
          );

          $response = new Response($twig->render($template, array('basket' => $basket)));
          $response->setCharset('UTF-8');

          return $response;
        }
      )->assert('ssel_id', '\d+');

      $app->post('/ajax/SET_NOTE/{sselcont_id}/', function (\Silex\Application $app, $sselcont_id)
        {
          $output = array('error' => true, 'datas' => _('Erreur lors de l\'enregistrement des donnees'));

          $request = $app['request'];
          $note    = $request->get('note');

          if (is_null($note))
          {
            Return new Response('You must provide a note value', 400);
          }

          $em = $app['Core']->getEntityManager();

          /* @var $repository \Repositories\BasketElementRepository */
          $repository = $em->getRepository('\Entities\BasketElement');

          $basket_element = $repository->findUserElement($sselcont_id, $app['Core']->getAuthenticatedUser());

          $validationDatas = $basket_element->getUserValidationDatas($app['Core']->getAuthenticatedUser());

          $validationDatas->setNote($note);

          $em->merge($validationDatas);

          $em->flush();

          /* @var $twig \Twig_Environment */
          $twig = $app['Core']->getTwig();

          $browser = \Browser::getInstance();

          if ($browser->isMobile())
          {
            $datas = $twig->render('lightbox/sc_note.twig', array('basket_element' => $basket_element));

            $output = array('error' => false, 'datas' => $datas);
          }
          else
          {
            $template = 'lightbox/sc_note.twig';

            $datas = $twig->render($template, array('basket_element' => $basket_element));

            $output = array('error' => false, 'datas' => $datas);
          }

          $Serializer = $app['Core']['Serializer'];

          return new Response(
              $Serializer->serialize($output, 'json')
              , 200
              , array('Content-Type' => 'application/json')
          );
        }
      )->assert('sselcont_id', '\d+');

      $app->post('/ajax/SET_ELEMENT_AGREEMENT/{sselcont_id}/', function(\Silex\Application $app, $sselcont_id)
        {
          $request   = $app['request'];
          $agreement = $request->get('agreement');

          if (is_null($agreement))
          {
            Return new Response('You must provide an agreement value', 400);
          }

          $agreement = $agreement > 0;

          $releasable = false;
          try
          {
            $ret = array(
              'error'      => true,
              'releasable' => false,
              'datas'      => _('Erreur lors de la mise a jour des donnes ')
            );

            $user       = $app['Core']->getAuthenticatedUser();
            $em         = $app['Core']->getEntityManager();
            $repository = $em->getRepository('\Entities\BasketElement');

            /* @var $repository \Repositories\BasketElementRepository */
            $basket_element = $repository->findUserElement(
              $sselcont_id
              , $user
            );
            /* @var $basket_element \Entities\BasketElement */
            $validationDatas = $basket_element->getUserValidationDatas($user);

            if(!$basket_element->getBasket()
              ->getValidation()
              ->getParticipant($user)->getCanAgree())
            {
              throw new ControllerException('You can not agree on this');
            }

            $validationDatas->setAgreement($agreement);

            $participant = $basket_element->getBasket()
              ->getValidation()
              ->getParticipant($user);

            $em->merge($basket_element);

            $em->flush();

            $releasable = false;
            if($participant->isReleasable() === true)
            {
              $releasable = _('Do you want to send your report ?');
            }

            $ret = array(
              'error'      => false
              , 'datas'      => ''
              , 'releasable' => $releasable
            );

          }
          catch(ControllerException $e)
          {
            $ret['datas'] = $e->getMessage();
          }
          $Serializer = $app['Core']['Serializer'];

          return new Response(
              $Serializer->serialize($ret, 'json')
              , 200
              , array('Content-Type' => 'application/json')
          );
        }
      )->assert('sselcont_id', '\d+');


      $app->post('/ajax/SET_RELEASE/{ssel_id}/', function(\Silex\Application $app, $ssel_id) use ($session, $appbox)
        {

          $em = $app['Core']->getEntityManager();

          $user = $app['Core']->getAuthenticatedUser();

          $repository = $em->getRepository('\Entities\Basket');

          $datas = array('error' => true, 'datas' => '');

          try
          {
            /* @var $repository \Repositories\BasketRepository */
            $basket = $repository->findUserBasket(
              $ssel_id
              , $user
              , false
            );

            if (!$basket->getValidation())
            {
              throw new ControllerException('There is no validation session attached to this basket');
            }

            if (!$basket->getValidation()->getParticipant($user)->getCanAgree())
            {
              throw new ControllerException('You have not right to agree');
            }

            /* @var $basket \Entities\Basket */
            $participant = $basket->getValidation()->getParticipant($user);
            $participant->setIsConfirmed(true);

            $em->merge($participant);

            $em->flush();

            $datas = array('error' => false, 'datas' => _('Envoie avec succes'));
          }
          catch(ControllerException $e)
          {
            $datas = array('error' => true, 'datas' => $e->getMessage());
          }

          $Serializer = $app['Core']['Serializer'];

          $response = new Response(
              $Serializer->serialize($datas, 'json')
              , 200
              , array('Content-Type' => 'application/json')
          );

          $response->setCharset('UTF-8');

          return $response;
        }
      )->assert('ssel_id', '\d+');



      $app->error(function($e) use($app)
        {
        
        var_dump($e->getMessage());
          /* @var $twig \Twig_Environment */
          $twig     = $app['Core']->getTwig();
          $registry = \registry::get_instance();

          $template = 'lightbox/error.twig';

          if ($registry->get('GV_debug'))
          {
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
          }
          else
          {
            $options = array(
              'module'      => 'validation',
              'module_name' => _('admin::monitor: module validation'),
              'error'       => ''
            );
          }
          $output       = $twig->render($template, $options);
          $response     = new Response($output, 404);
          $response->setCharset('UTF-8');

          return $response;
        });

      return $app;
    }
);
