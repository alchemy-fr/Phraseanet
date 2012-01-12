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
    Alchemy\Phrasea\Controller\Exception as ControllerException;
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
              $push = new RecordHelper\Push($app['Core'], $app['request']);

              $template = 'prod/actions/Push.html.twig';

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response($twig->render($template, array('push' => $push, 'message' => '')));
            }
    );
    $controllers->post('/send/', function(Application $app)
            {
              $request = $app['request'];

              $ret = array(
                  'success' => false,
                  'message' => _('Unable to send the documents')
              );

              try
              {
                $em = $app['Core']->getEntityManager();

                $pusher = new RecordHelper\Push($app['Core'], $app['request']);

                $user = $app['Core']->getAuthenticatedUser();

                $appbox = \appbox::get_instance();

                $push_name = $request->get(
                        'push_name'
                        , sprintf(_('Push from %s'), $user->get_display_name())
                );

                $push_description = $request->get('push_description');

                $receivers = $request->get('receivers');

                if (!is_array($receivers) || count($receivers) === 0)
                {
                  throw new ControllerException(_('No receivers specified'));
                }

                if (!is_array($pusher->get_elements()) || count($pusher->get_elements()) === 0)
                {
                  throw new ControllerException(_('No elements to push'));
                }

                foreach ($receivers as $receiver)
                {
                  try
                  {
                    $user_receiver = \User_Adapter::getInstance($receiver['usr_id'], $appbox);
                  }
                  catch (\Exception $e)
                  {
                    throw new ControllerException(sprintf(_('Unknown user %d'), $receiver['usr_id']));
                  }

                  $Basket = new \Entities\Basket();
                  $Basket->setName($push_name);
                  $Basket->setDescription($push_description);
                  $Basket->setOwner($user_receiver);
                  $Basket->setPusher($user);

                  $em->persist($Basket);

                  foreach ($pusher->get_elements() as $element)
                  {
                    $BasketElement = new \Entities\BasketELement();
                    $BasketElement->setRecord($element);
                    $BasketElement->setBasket($Basket);


                    if ($receiver['HD'])
                    {
                      $user_receiver->ACL()->grant_hd_on(
                              $BasketElement->getRecord()
                              , $user
                              , \ACL::GRANT_ACTION_PUSH
                      );
                    }
                    else
                    {
                      $user_receiver->ACL()->grant_preview_on(
                              $BasketElement->getRecord()
                              , $user
                              , \ACL::GRANT_ACTION_PUSH
                      );
                    }

                    $em->persist($BasketElement);
                  }
                }

                $em->flush();

                $message = sprintf(
                        _('%1$d records have been sent to %2$d users')
                        , count($pusher->get_elements())
                        , count($request->get('receivers'))
                );

                $ret = array(
                    'success' => true,
                    'message' => $message
                );
              }
              catch (ControllerException $e)
              {
                $ret['message'] = $e->getMessage();
              }

              $Json = $app['Core']['Serializer']->serialize($ret, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    $controllers->post('/validate/', function(Application $app)
            {
              $request = $app['request'];

              $ret = array(
                  'success' => false,
                  'message' => _('Unable to send the documents')
              );

              try
              {
                $pusher = new RecordHelper\Push($app['Core'], $app['request']);
                $user = $app['Core']->getAuthenticatedUser();

                $em = $app['Core']->getEntityManager();

                $repository = $em->getRepository('\Entities\Basket');

                $validation_name = $request->get(
                        'validation_name'
                        , sprintf(_('Validation from %s'), $user->get_display_name())
                );

                $validation_description = $request->get('validation_description');

                $participants = $request->get('participants');

                if (!is_array($participants) || count($participants) === 0)
                {
                  throw new ControllerException(_('No participants specified'));
                }

                if (!is_array($pusher->get_elements()) || count($pusher->get_elements()) === 0)
                {
                  throw new ControllerException(_('No elements to validate'));
                }

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

                foreach ($participants as $participant)
                {
                  try
                  {
                    $participant_user = \User_Adapter::getInstance($participant['usr_id'], $appbox);
                  }
                  catch (\Exception $e)
                  {
                    throw new ControllerException(sprintf(_('Unknown user %d'), $receiver['usr_id']));
                  }

                  try
                  {
                    $Participant = $Validation->getParticipant($participant_user);
                    continue;
                  }
                  catch (\Exception_NotFound $e)
                  {
                    
                  }

                  $Participant = new \Entities\ValidationParticipant();
                  $Participant->setUser($participant_user);
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

                    if ($participant['HD'])
                    {
                      $participant_user->ACL()->grant_hd_on(
                              $BasketElement->getRecord()
                              , $user
                              , \ACL::GRANT_ACTION_VALIDATE
                      );
                    }
                    else
                    {
                      $participant_user->ACL()->grant_preview_on(
                              $BasketElement->getRecord()
                              , $user
                              , \ACL::GRANT_ACTION_VALIDATE
                      );
                    }

                    $em->merge($BasketElement);
                    $em->persists($ValidationData);

                    $Participant->addValidationData($ValidationData);
                  }

                  $em->merge($Participant);
                }

                $em->merge($Basket);
                $em->merge($Validation);

                $em->flush();

                $message = sprintf(
                        _('%1$d records have been sent to %2$d users')
                        , count($pusher->get_elements())
                        , count($request->get('receivers'))
                );

                $ret = array(
                    'success' => true,
                    'message' => $message
                );
              }
              catch (ControllerException $e)
              {
                $ret['message'] = $e->getMessage();
              }

              $Json = $app['Core']['Serializer']->serialize($ret, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    $controllers->get('/search-user/', function(Application $app)
            {
              $request = $app['request'];
              $em = $app['Core']->getEntityManager();
              $user = $app['Core']->getAuthenticatedUser();

              $query = new \User_Query(\appbox::get_instance());

              $query->on_bases_where_i_am($user->ACL(), array('canpush'));

              $query->like(\User_Query::LIKE_FIRSTNAME, $request->get('query'))
                ->like(\User_Query::LIKE_LASTNAME, $request->get('query'))
                ->like(\User_Query::LIKE_LOGIN, $request->get('query'))
                ->like_match(\User_Query::LIKE_MATCH_OR);
              
              $result = $query->include_phantoms()
                              ->limit(0, 50)
                              ->execute()->get_results();

              $repository = $em->getRepository('\Entities\UsrList');

              $lists = $repository->findUserListLike($user, $request->get('query'));

              $datas = array();

              if ($lists)
              {
                foreach ($lists as $list)
                {
                  $datas[] = array(
                      'type' => 'LIST'
                      , 'name' => $list->getName()
                      , 'quantity' => $list->getUsers()->count()
                  );
                }
              }

              if ($result)
              {
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
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );



    return $controllers;
  }

}
