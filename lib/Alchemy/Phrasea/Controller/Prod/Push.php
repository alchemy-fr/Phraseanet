<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Silex\ControllerCollection;
use Alchemy\Phrasea\Helper\Record as RecordHelper,
    Alchemy\Phrasea\Out\Module\PDF as PDFExport;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Push implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->get('/', function(Application $app)
            {
              $pusher = new RecordHelper\Push($app['Core']);

              $template = 'prod/actions/printer_default.html.twig';

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render($template, array('printer' => $printer, 'message' => ''));
            }
    );
    $controllers->post('/send/', function(Application $app)
            {
              $pusher = new RecordHelper\Push($app['Core']);
              $user = $app['Core']->getAuthenticatedUser();
              $appbox = \appbox::get_instance();

              $push_name = $request->get(
                      'push_name'
                      , sprintf(_('Push from %s', $user->get_display_name()))
              );
              $push_description = $request->get('push_description');

              foreach ($request->get('receivers') as $receiver)
              {
                $user_receiver = \User_Adapter::getInstance($receiver, $appbox);

                $Basket = new Basket();
                $Basket->setName($push_name);
                $Basket->setDescription($push_description);
                $Basket->setUser($user_receiver);
                $Basket->setPusher($user);

                $em->persist($Basket);

                foreach ($pusher->get_elements() as $element)
                {
                  $BasketElement = new BasketELement();
                  $BasketElement->setRecord($element);
                  $BasketElement->setBasket($Basket);

                  $em->persist($BasketElement);
                }
              }

              $em->flush();
            }
    );

    $controllers->post('/validate/', function(Application $app)
            {
              $request = $app['request'];

              $pusher = new RecordHelper\Push($app['Core']);
              $user = $app['Core']->getAuthenticatedUser();

              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\Basket');

              $validation_name = $request->get(
                      'validation_name'
                      , sprintf(_('Validation from %s'), $user->get_display_name())
              );
              $validation_description = $request->get('validation_description');

              if ($pusher->is_basket())
              {
                $Basket = $pusher->get_original_basket();
              }
              else
              {
                $Basket = new \Entities\Basket();
                $Basket->setName($validation_name);
                $Basket->setDescription($validation_description);
                $Basket->setOwner($user);

                $em->persist($Basket);

                foreach ($pusher->get_elements() as $element)
                {
                  $BasketElement = new \Entities\BasketElement();
                  $BasketElement->setRecord($element);
                  $BasketElement->setBasket($Basket);

                  $em->persist($BasketElement);
                }

                $em->flush();
              }

              if (!$Basket->getValidation())
              {
                $Validation = new \Entities\ValidationSession();
                $Validation->setInitiator($app['Core']->getAuthenticatedUser());
                $Validation->setBasket($Basket);

                $Basket->setValidation($Validation);
                $em->persist($Validation);
              }
              else
              {
                $Validation = $Basket->getValidation();
              }


              $appbox = \appbox::get_instance();

              foreach ($request->get('participants') as $participant)
              {
                $user = \User_Adapter::getInstance($participant['usr_id'], $appbox);

                try
                {
                  $Participant = $Validation->getParticipant($user);
                  continue;
                }
                catch (\Exception_NotFound $e)
                {
                  
                }

                $Participant = new \Entities\ValidationParticipant();
                $Participant->setUser($user);
                $Participant->setSession($Validation);

                $Participant->setCanAgree($participant['agree']);
                $Participant->setCanSeeOthers($participant['see_others']);

                $em->persist($Participant);

                foreach ($Basket->getElements() as $BasketElement)
                {
                  $ValidationData = new \Entities\ValidationData();
                  $ValidationData->setParticipant($Participant);
                  $validationData->setBasketElement($BasketElement);
                  $BasketElement->addValidationData($ValidationData);

                  $em->merge($BasketElement);
                  $em->persists($ValidationData);

                  $Participant->addValidationData($ValidationData);
                }

                $em->merge($Participant);
              }

              $em->merge($Basket);
              $em->merge($Validation);

              $em->flush();
            }
    );

    $controllers->get('/search-user/', function(Application $app)
            {
              $request = $app['request'];
              $em = $app['Core']->getEntityManager();
              $user = $app['Core']->getAuthenticatedUser();

              $pusher = new RecordHelper\Push($app['Core']);

              $result = $pusher->search($request->get('query'));

              $repository = $em->getRepository('\Entities\UsrList');

              $lists = $repository->findUserListLike($user, $request->get('query'));

              $datas = array();

              foreach ($lists as $list)
              {
                $datas[] = array(
                    'type' => 'LIST'
                    , 'name' => $list->getName()
                    , 'quantity' => $list->getUsers()->count()
                );
              }

              foreach ($result as $user)
              {
                $datas[] = array(
                    'type' => 'USER'
                    , 'usr_id' => $user->get_id()
                    , 'firstname' => $user->get_firstname()
                    , 'lastname' => $user->get_lastname()
                    , 'email' => $user->get_email()
                    , 'display_name' => $user->get_display_name()
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );



    return $controllers;
  }

}
