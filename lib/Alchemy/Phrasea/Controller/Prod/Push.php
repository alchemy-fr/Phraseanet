<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Push implements ControllerProviderInterface
{

    protected function getUserFormatter()
    {
        return function(\User_Adapter $user) {
                return array(
                    'type'         => 'USER'
                    , 'usr_id'       => $user->get_id()
                    , 'firstname'    => $user->get_firstname()
                    , 'lastname'     => $user->get_lastname()
                    , 'email'        => $user->get_email()
                    , 'display_name' => $user->get_display_name()
                    , 'subtitle'     => sprintf('%s, %s', $user->get_job(), $user->get_company())
                );
            };
    }

    protected function getListFormatter()
    {
        $userFormatter = $this->getUserFormatter();

        return function(\Entities\UsrList $List) use ($userFormatter) {
                $entries = array();

                foreach ($List->getEntries() as $entry) {
                    /* @var $entry \Entities\UsrListEntry */
                    $entries[] = array(
                        'Id'   => $entry->getId(),
                        'User' => $userFormatter($entry->getUser())
                    );
                }

                return array(
                    'type'    => 'LIST'
                    , 'list_id' => $List->getId()
                    , 'name'    => $List->getName()
                    , 'length'  => count($entries)
                    , 'entries' => $entries
                );
            };
    }

    protected function getUsersInSelectionExtractor()
    {
        return function(array $selection) {
                $Users = new \Doctrine\Common\Collections\ArrayCollection();

                foreach ($selection as $record) {
                    /* @var $record record_adapter */
                    foreach ($record->get_caption()->get_fields() as $caption_field) {
                        foreach ($caption_field->get_values() as $value) {
                            if ( ! $value->getVocabularyType())
                                continue;

                            if ($value->getVocabularyType()->getType() !== 'User')
                                continue;

                            $user = $value->getRessource();

                            $Users->set($user->get_id(), $user);
                        }
                    }
                }

                return $Users;
            };
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $userFormatter = $this->getUserFormatter();

        $listFormatter = $this->getListFormatter();

        $userSelection = $this->getUsersInSelectionExtractor();

        $controllers->post('/sendform/', function(Application $app) use ($userSelection) {
                $push = new RecordHelper\Push($app['Core'], $app['request']);

                $em = $app['Core']->getEntityManager();
                $repository = $em->getRepository('\Entities\UsrList');

                $RecommendedUsers = $userSelection($push->get_elements());

                $params = array(
                    'push'             => $push,
                    'message'          => '',
                    'lists'            => $repository->findUserLists($app['Core']->getAuthenticatedUser()),
                    'context'          => 'Push',
                    'RecommendedUsers' => $RecommendedUsers
                );

                $template = 'prod/actions/Push.html.twig';

                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                return new Response($twig->render($template, $params));
            }
        );

        $controllers->post('/validateform/', function(Application $app) use ($userSelection) {
                $push = new RecordHelper\Push($app['Core'], $app['request']);

                $em = $app['Core']->getEntityManager();
                $repository = $em->getRepository('\Entities\UsrList');

                $RecommendedUsers = $userSelection($push->get_elements());

                $params = array(
                    'push'             => $push,
                    'message'          => '',
                    'lists'            => $repository->findUserLists($app['Core']->getAuthenticatedUser()),
                    'context'          => 'Feedback',
                    'RecommendedUsers' => $RecommendedUsers
                );

                $template = 'prod/actions/Push.html.twig';

                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                return new Response($twig->render($template, $params));
            }
        );

        $controllers->post('/send/', function(Application $app) {
                $request = $app['request'];

                $ret = array(
                    'success' => false,
                    'message' => _('Unable to send the documents')
                );

                try {
                    $em = $app['Core']->getEntityManager();

                    $registry = $app['Core']->getRegistry();

                    $pusher = new RecordHelper\Push($app['Core'], $app['request']);

                    $user = $app['Core']->getAuthenticatedUser();

                    $appbox = \appbox::get_instance($app['Core']);

                    $push_name = $request->get('name');

                    if (trim($push_name) === '') {
                        $push_name = sprintf(_('Push from %s'), $user->get_display_name());
                    }

                    $push_description = $request->get('push_description');

                    $receivers = $request->get('participants');

                    if ( ! is_array($receivers) || count($receivers) === 0) {
                        throw new ControllerException(_('No receivers specified'));
                    }

                    if ( ! is_array($pusher->get_elements()) || count($pusher->get_elements()) === 0) {
                        throw new ControllerException(_('No elements to push'));
                    }

                    $events_manager = $app['Core']['events-manager'];

                    foreach ($receivers as $receiver) {
                        try {
                            $user_receiver = \User_Adapter::getInstance($receiver['usr_id'], $appbox);
                        } catch (\Exception $e) {
                            throw new ControllerException(sprintf(_('Unknown user %d'), $receiver['usr_id']));
                        }

                        $Basket = new \Entities\Basket();
                        $Basket->setName($push_name);
                        $Basket->setDescription($push_description);
                        $Basket->setOwner($user_receiver);
                        $Basket->setPusher($user);
                        $Basket->setIsRead(false);

                        $em->persist($Basket);

                        foreach ($pusher->get_elements() as $element) {
                            $BasketElement = new \Entities\BasketElement();
                            $BasketElement->setRecord($element);
                            $BasketElement->setBasket($Basket);

                            $em->persist($BasketElement);

                            $Basket->addBasketElement($BasketElement);

                            if ($receiver['HD']) {
                                $user_receiver->ACL()->grant_hd_on(
                                    $BasketElement->getRecord()
                                    , $user
                                    , \ACL::GRANT_ACTION_PUSH
                                );
                            } else {
                                $user_receiver->ACL()->grant_preview_on(
                                    $BasketElement->getRecord()
                                    , $user
                                    , \ACL::GRANT_ACTION_PUSH
                                );
                            }
                        }

                        $em->flush();

                        $url = $registry->get('GV_ServerName')
                            . 'lightbox/index.php?LOG='
                            . \random::getUrlToken(\random::TYPE_VALIDATE, $user_receiver->get_id(), null, $Basket->getId());

                        $params = array(
                            'from'       => $user->get_id()
                            , 'from_email' => $user->get_email()
                            , 'to'         => $user_receiver->get_id()
                            , 'to_email'   => $user_receiver->get_email()
                            , 'to_name'    => $user_receiver->get_display_name()
                            , 'url'        => $url
                            , 'accuse'     => ! ! $request->get('recept', false)
                            , 'message'    => $request->get('message')
                            , 'ssel_id'    => $Basket->getId()
                        );

                        $events_manager->trigger('__PUSH_DATAS__', $params);
                    }

                    $appbox->get_session()->get_logger($BasketElement->getRecord()->get_databox())
                        ->log($BasketElement->getRecord(), \Session_Logger::EVENT_VALIDATE, $user_receiver->get_id(), '');

                    $em->flush();

                    $message = sprintf(
                        _('%1$d records have been sent to %2$d users')
                        , count($pusher->get_elements())
                        , count($receivers)
                    );

                    $ret = array(
                        'success' => true,
                        'message' => $message
                    );
                } catch (ControllerException $e) {
                    $ret['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
                }

                $Json = $app['Core']['Serializer']->serialize($ret, 'json');

                return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
        );

        $controllers->post('/validate/', function(Application $app) {
                $request = $app['request'];
                $appbox = \appbox::get_instance($app['Core']);

                $ret = array(
                    'success' => false,
                    'message' => _('Unable to send the documents')
                );

                $em = $app['Core']->getEntityManager();

                $registry = $app['Core']->getRegistry();

                /* @var $em \Doctrine\ORM\EntityManager */
                $em->beginTransaction();

                try {
                    $pusher = new RecordHelper\Push($app['Core'], $app['request']);
                    $user = $app['Core']->getAuthenticatedUser();

                    $events_manager = $app['Core']['events-manager'];

                    $repository = $em->getRepository('\Entities\Basket');

                    $validation_name = $request->get('name');

                    if (trim($validation_name) === '') {
                        $validation_name = sprintf(_('Validation from %s'), $user->get_display_name());
                    }

                    $validation_description = $request->get('validation_description');

                    $participants = $request->get('participants');

                    if ( ! is_array($participants) || count($participants) === 0) {
                        throw new ControllerException(_('No participants specified'));
                    }

                    if ( ! is_array($pusher->get_elements()) || count($pusher->get_elements()) === 0) {
                        throw new ControllerException(_('No elements to validate'));
                    }

                    if ($pusher->is_basket()) {
                        $Basket = $pusher->get_original_basket();
                    } else {
                        $Basket = new \Entities\Basket();
                        $Basket->setName($validation_name);
                        $Basket->setDescription($validation_description);
                        $Basket->setOwner($user);
                        $Basket->setIsRead(false);

                        $em->persist($Basket);

                        foreach ($pusher->get_elements() as $element) {
                            $BasketElement = new \Entities\BasketElement();
                            $BasketElement->setRecord($element);
                            $BasketElement->setBasket($Basket);

                            $em->persist($BasketElement);

                            $Basket->addBasketElement($BasketElement);
                        }
                        $em->flush();
                    }

                    $em->refresh($Basket);

                    if ( ! $Basket->getValidation()) {
                        $Validation = new \Entities\ValidationSession();
                        $Validation->setInitiator($app['Core']->getAuthenticatedUser());
                        $Validation->setBasket($Basket);

                        $duration = (int) $request->get('duration');

                        if ($duration > 0) {
                            $date = new \DateTime('+' . $duration . ' day' . ($duration > 1 ? 's' : ''));
                            $Validation->setExpires($date);
                        }

                        $Basket->setValidation($Validation);
                        $em->persist($Validation);
                    } else {
                        $Validation = $Basket->getValidation();
                    }

                    $appbox = \appbox::get_instance($app['Core']);

                    $found = false;
                    foreach ($participants as $key => $participant) {
                        if ($participant['usr_id'] == $user->get_id()) {
                            $found = true;
                            break;
                        }
                    }

                    if ( ! $found) {
                        $participants[$user->get_id()] = array(
                            'see_others' => 1,
                            'usr_id'     => $user->get_id(),
                            'agree'      => 0,
                            'HD'         => 0
                        );
                    }

                    foreach ($participants as $key => $participant) {
                        foreach (array('see_others', 'usr_id', 'agree', 'HD') as $mandatoryparam) {
                            if ( ! array_key_exists($mandatoryparam, $participant))
                                throw new ControllerException(sprintf(_('Missing mandatory parameter %s'), $mandatoryparam));
                        }

                        try {
                            $participant_user = \User_Adapter::getInstance($participant['usr_id'], $appbox);
                        } catch (\Exception $e) {
                            throw new ControllerException(sprintf(_('Unknown user %d'), $receiver['usr_id']));
                        }

                        try {
                            $Participant = $Validation->getParticipant($participant_user);
                            continue;
                        } catch (\Exception_NotFound $e) {

                        }

                        $Participant = new \Entities\ValidationParticipant();
                        $Participant->setUser($participant_user);
                        $Participant->setSession($Validation);

                        $Participant->setCanAgree($participant['agree']);
                        $Participant->setCanSeeOthers($participant['see_others']);

                        $em->persist($Participant);

                        foreach ($Basket->getElements() as $BasketElement) {
                            $ValidationData = new \Entities\ValidationData();
                            $ValidationData->setParticipant($Participant);
                            $ValidationData->setBasketElement($BasketElement);
                            $BasketElement->addValidationData($ValidationData);

                            if ($participant['HD']) {
                                $participant_user->ACL()->grant_hd_on(
                                    $BasketElement->getRecord()
                                    , $user
                                    , \ACL::GRANT_ACTION_VALIDATE
                                );
                            } else {
                                $participant_user->ACL()->grant_preview_on(
                                    $BasketElement->getRecord()
                                    , $user
                                    , \ACL::GRANT_ACTION_VALIDATE
                                );
                            }

                            $em->merge($BasketElement);
                            $em->persist($ValidationData);

                            $appbox->get_session()->get_logger($BasketElement->getRecord()->get_databox())
                                ->log($BasketElement->getRecord(), \Session_Logger::EVENT_PUSH, $participant_user->get_id(), '');

                            $Participant->addValidationData($ValidationData);
                        }

                        $Participant = $em->merge($Participant);

                        $em->flush();

                        $url = $registry->get('GV_ServerName')
                            . 'lightbox/index.php?LOG='
                            . \random::getUrlToken(\random::TYPE_VIEW, $participant_user->get_id(), null, $Basket->getId());

                        $params = array(
                            'from'       => $user->get_id()
                            , 'from_email' => $user->get_email()
                            , 'to'         => $participant_user->get_id()
                            , 'to_email'   => $participant_user->get_email()
                            , 'to_name'    => $participant_user->get_display_name()
                            , 'url'        => $url
                            , 'accuse'     => ! ! $request->get('recept', false)
                            , 'message'    => $request->get('message')
                            , 'ssel_id'    => $Basket->getId()
                        );

                        $events_manager->trigger('__PUSH_VALIDATION__', $params);
                    }

                    $Basket = $em->merge($Basket);
                    $Validation = $em->merge($Validation);

                    $em->flush();

                    $message = sprintf(
                        _('%1$d records have been sent for validation to %2$d users')
                        , count($pusher->get_elements())
                        , count($request->get('participants'))
                    );

                    $ret = array(
                        'success' => true,
                        'message' => $message
                    );

                    $em->commit();
                } catch (ControllerException $e) {
                    $ret['message'] = $e->getMessage();
                    $em->rollback();
                }

                $Json = $app['Core']['Serializer']->serialize($ret, 'json');

                return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
        );

        $controllers->get('/user/{usr_id}/', function(Application $app, $usr_id) use ($userFormatter) {

                $datas = null;

                $request = $app['request'];
                $em = $app['Core']->getEntityManager();
                $user = $app['Core']->getAuthenticatedUser();

                $query = new \User_Query(\appbox::get_instance($app['Core']));

                $query->on_bases_where_i_am($user->ACL(), array('canpush'));

                $query->in(array($usr_id));

                $result = $query->include_phantoms()
                        ->limit(0, 1)
                        ->execute()->get_results();

                if ($result) {
                    foreach ($result as $user) {
                        $datas = $userFormatter($user);
                    }
                }

                $Json = $app['Core']['Serializer']->serialize($datas, 'json');

                return new Response($Json, 200, array('Content-Type' => 'application/json'));
            })->assert('usr_id', '\d+');

        $controllers->get('/list/{list_id}/', function(Application $app, $list_id) use ($listFormatter) {
                $datas = null;

                $em = $app['Core']->getEntityManager();
                $user = $app['Core']->getAuthenticatedUser();

                $repository = $em->getRepository('\Entities\UsrList');

                $list = $repository->findUserListByUserAndId($user, $list_id);

                if ($list) {
                    $datas = $listFormatter($list);
                }

                $Json = $app['Core']['Serializer']->serialize($datas, 'json');

                return new Response($Json, 200, array('Content-Type' => 'application/json'));
            })->assert('list_id', '\d+');

        $controllers->post('/add-user/', function(Application $app, Request $request) use ($userFormatter) {
                $result = array('success' => false, 'message' => '', 'user'    => null);

                $Serializer = $app['Core']['Serializer'];

                $AdminUser = $app['Core']->getAuthenticatedUser();

                try {
                    /* @var $AdminUser \User_Adapter */
                    if ( ! $AdminUser->ACL()->has_right('manageusers'))
                        throw new ControllerException(_('You are not allowed to add users'));

                    if ( ! $request->get('firstname'))
                        throw new ControllerException(_('First name is required'));

                    if ( ! $request->get('lastname'))
                        throw new ControllerException(_('Last name is required'));

                    if ( ! $request->get('email'))
                        throw new ControllerException(_('Email is required'));

                    if ( ! \mail::validateEmail($request->get('email')))
                        throw new ControllerException(_('Email is invalid'));
                } catch (ControllerException $e) {
                    $result['message'] = $e->getMessage();

                    return new Response($Serializer->serialize($result, 'json'), 200, array('Content-Type' => 'application/json'));
                }

                $appbox = \appbox::get_instance($app['Core']);

                $user = null;
                $email = $request->get('email');

                try {
                    $usr_id = \User_Adapter::get_usr_id_from_email($email);
                    $user = \User_Adapter::getInstance($usr_id, $appbox);

                    $result['message'] = _('User already exists');
                    $result['success'] = true;
                    $result['user'] = $userFormatter($user);
                } catch (\Exception $e) {

                }

                if ( ! $user instanceof \User_Adapter) {
                    try {
                        $password = \random::generatePassword();

                        $user = \User_Adapter::create($appbox, $email, $password, $email, false);

                        $user->set_firstname($request->get('firstname'))
                            ->set_lastname($request->get('lastname'));

                        if ($request->get('company'))
                            $user->set_company($request->get('company'));
                        if ($request->get('job'))
                            $user->set_company($request->get('job'));
                        if ($request->get('form_geonameid'))
                            $user->set_geonameid($request->get('form_geonameid'));

                        $result['message'] = _('User successfully created');
                        $result['success'] = true;
                        $result['user'] = $userFormatter($user);
                    } catch (\Exception $e) {
                        $result['message'] = _('Error while creating user');
                    }
                }

                return new Response($Serializer->serialize($result, 'json'), 200, array('Content-Type' => 'application/json'));
            });

        $controllers->get('/add-user/', function(Application $app, Request $request) {
                $params = array('callback' => $request->get('callback'));

                return new Response($app['Core']['Twig']->render('prod/User/Add.html.twig', $params));
            });

        $controllers->get('/search-user/', function(Application $app) use ($userFormatter, $listFormatter) {
                $request = $app['request'];
                $em = $app['Core']->getEntityManager();
                $user = $app['Core']->getAuthenticatedUser();

                $query = new \User_Query(\appbox::get_instance($app['Core']));

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

                if ($lists) {
                    foreach ($lists as $list) {
                        $datas[] = $listFormatter($list);
                    }
                }

                if ($result) {
                    foreach ($result as $user) {
                        $datas[] = $userFormatter($user);
                    }
                }

                $Json = $app['Core']['Serializer']->serialize($datas, 'json');

                return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
        );

        $controllers->match('/edit-list/{list_id}/', function(Application $app, Request $request, $list_id) {

                $user = $app['Core']->getAuthenticatedUser();
                $em = $app['Core']->getEntityManager();

                $repository = $em->getRepository('\Entities\UsrList');

                $list = $repository->findUserListByUserAndId($user, $list_id);

                $query = new \User_Query(\appbox::get_instance($app['Core']));

                $query->on_bases_where_i_am($user->ACL(), array('canpush'));

                if ($request->get('query')) {
                    $query->like($request->get('like_field'), $request->get('query'))
                        ->like_match(\User_Query::LIKE_MATCH_OR);
                }
                if (is_array($request->get('Activity'))) {
                    $query->haveActivities($request->get('Activity'));
                }
                if (is_array($request->get('Template'))) {
                    $query->haveTemplate($request->get('Template'));
                }
                if (is_array($request->get('Company'))) {
                    $query->inCompanies($request->get('Company'));
                }
                if (is_array($request->get('Country'))) {
                    $query->inCountries($request->get('Country'));
                }
                if (is_array($request->get('Position'))) {
                    $query->havePositions($request->get('Position'));
                }

                $sort = $request->get('srt', 'usr_creationdate');
                $ord = $request->get('ord', 'desc');

                $perPage = 10;
                $offset_start = Max(((int) $request->get('page') - 1) * $perPage, 0);

                $query->sort_by($sort, $ord);

                $results = $query->include_phantoms()
                        ->limit($offset_start, $perPage)
                        ->execute()->get_results();

                $params = array(
                    'query'   => $query
                    , 'results' => $results
                    , 'list'    => $list
                    , 'sort'    => $sort
                    , 'ord'     => $ord
                );

                if ($request->get('type') === 'fragment') {
                    return new Response(
                            $app['Core']->getTwig()->render('prod/actions/Feedback/ResultTable.html.twig', $params)
                    );
                } else {
                    return new Response(
                            $app['Core']->getTwig()->render('prod/actions/Feedback/list.html.twig', $params)
                    );
                }
            }
        )->assert('list_id', '\d+');

        return $controllers;
    }
}
