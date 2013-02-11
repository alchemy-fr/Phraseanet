<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Alchemy\Phrasea\Controller\Exception as ControllerException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

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
            $subtitle = array_filter(array($user->get_job(), $user->get_company()));

            return array(
                'type'         => 'USER'
                , 'usr_id'       => $user->get_id()
                , 'firstname'    => $user->get_firstname()
                , 'lastname'     => $user->get_lastname()
                , 'email'        => $user->get_email()
                , 'display_name' => $user->get_display_name()
                , 'subtitle'     => implode(', ', $subtitle)
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
                    'User' => $userFormatter($entry->getUser($app))
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
                        if (!$value->getVocabularyType())
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

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAuthentication()
                ->requireRight('push');
        });

        $userFormatter = $this->getUserFormatter();

        $listFormatter = $this->getListFormatter();

        $userSelection = $this->getUsersInSelectionExtractor();

        $controllers->post('/sendform/', function(Application $app) use ($userSelection) {
            $push = new RecordHelper\Push($app, $app['request']);

            $repository = $app['EM']->getRepository('\Entities\UsrList');

            $RecommendedUsers = $userSelection($push->get_elements());

            $params = array(
                'push'             => $push,
                'message'          => '',
                'lists'            => $repository->findUserLists($app['phraseanet.user']),
                'context'          => 'Push',
                'RecommendedUsers' => $RecommendedUsers
            );

            return $app['twig']->render('prod/actions/Push.html.twig', $params);
        });

        $controllers->post('/validateform/', function(Application $app) use ($userSelection) {
            $push = new RecordHelper\Push($app, $app['request']);

            $repository = $app['EM']->getRepository('\Entities\UsrList');

            $RecommendedUsers = $userSelection($push->get_elements());

            $params = array(
                'push'             => $push,
                'message'          => '',
                'lists'            => $repository->findUserLists($app['phraseanet.user']),
                'context'          => 'Feedback',
                'RecommendedUsers' => $RecommendedUsers
            );

            return $app['twig']->render('prod/actions/Push.html.twig', $params);
        });

        $controllers->post('/send/', function(Application $app) {
            $request = $app['request'];

            $ret = array(
                'success' => false,
                'message' => _('Unable to send the documents')
            );

            try {
                $pusher = new RecordHelper\Push($app, $app['request']);

                $push_name = $request->request->get('name');

                if (trim($push_name) === '') {
                    $push_name = sprintf(_('Push from %s'), $app['phraseanet.user']->get_display_name());
                }

                $push_description = $request->request->get('push_description');

                $receivers = $request->request->get('participants');

                if (!is_array($receivers) || count($receivers) === 0) {
                    throw new ControllerException(_('No receivers specified'));
                }

                if (!is_array($pusher->get_elements()) || count($pusher->get_elements()) === 0) {
                    throw new ControllerException(_('No elements to push'));
                }

                foreach ($receivers as $receiver) {
                    try {
                        $user_receiver = \User_Adapter::getInstance($receiver['usr_id'], $app);
                    } catch (\Exception $e) {
                        throw new ControllerException(sprintf(_('Unknown user %d'), $receiver['usr_id']));
                    }

                    $Basket = new \Entities\Basket();
                    $Basket->setName($push_name);
                    $Basket->setDescription($push_description);
                    $Basket->setOwner($user_receiver);
                    $Basket->setPusher($app['phraseanet.user']);
                    $Basket->setIsRead(false);

                    $app['EM']->persist($Basket);

                    foreach ($pusher->get_elements() as $element) {
                        $BasketElement = new \Entities\BasketElement();
                        $BasketElement->setRecord($element);
                        $BasketElement->setBasket($Basket);

                        $app['EM']->persist($BasketElement);

                        $Basket->addBasketElement($BasketElement);

                        if ($receiver['HD']) {
                            $user_receiver->ACL()->grant_hd_on(
                                $BasketElement->getRecord($app)
                                , $app['phraseanet.user']
                                , \ACL::GRANT_ACTION_PUSH
                            );
                        } else {
                            $user_receiver->ACL()->grant_preview_on(
                                $BasketElement->getRecord($app)
                                , $app['phraseanet.user']
                                , \ACL::GRANT_ACTION_PUSH
                            );
                        }
                    }

                    $app['EM']->flush();

                    $url = $app['phraseanet.registry']->get('GV_ServerName')
                        . 'lightbox/index.php?LOG='
                        . \random::getUrlToken($app, \random::TYPE_VALIDATE, $user_receiver->get_id(), null, $Basket->getId());

                    $params = array(
                        'from'       => $app['phraseanet.user']->get_id()
                        , 'from_email' => $app['phraseanet.user']->get_email()
                        , 'to'         => $user_receiver->get_id()
                        , 'to_email'   => $user_receiver->get_email()
                        , 'to_name'    => $user_receiver->get_display_name()
                        , 'url'        => $url
                        , 'accuse'     => !!$request->request->get('recept', false)
                        , 'message'    => $request->request->get('message')
                        , 'ssel_id'    => $Basket->getId()
                    );

                    $app['events-manager']->trigger('__PUSH_DATAS__', $params);
                }

                $app['phraseanet.logger']($BasketElement->getRecord($app)->get_databox())
                    ->log($BasketElement->getRecord($app), \Session_Logger::EVENT_VALIDATE, $user_receiver->get_id(), '');

                $app['EM']->flush();

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

            return $app->json($ret);
        });

        $controllers->post('/validate/', function(Application $app) {
            $request = $app['request'];

            $ret = array(
                'success' => false,
                'message' => _('Unable to send the documents')
            );

            $app['EM']->beginTransaction();

            try {
                $pusher = new RecordHelper\Push($app, $app['request']);

                $repository = $app['EM']->getRepository('\Entities\Basket');

                $validation_name = $request->request->get('name');

                if (trim($validation_name) === '') {
                    $validation_name = sprintf(_('Validation from %s'), $app['phraseanet.user']->get_display_name());
                }

                $validation_description = $request->request->get('validation_description');

                $participants = $request->request->get('participants');

                if (!is_array($participants) || count($participants) === 0) {
                    throw new ControllerException(_('No participants specified'));
                }

                if (!is_array($pusher->get_elements()) || count($pusher->get_elements()) === 0) {
                    throw new ControllerException(_('No elements to validate'));
                }

                if ($pusher->is_basket()) {
                    $Basket = $pusher->get_original_basket();
                } else {
                    $Basket = new \Entities\Basket();
                    $Basket->setName($validation_name);
                    $Basket->setDescription($validation_description);
                    $Basket->setOwner($app['phraseanet.user']);
                    $Basket->setIsRead(false);

                    $app['EM']->persist($Basket);

                    foreach ($pusher->get_elements() as $element) {
                        $BasketElement = new \Entities\BasketElement();
                        $BasketElement->setRecord($element);
                        $BasketElement->setBasket($Basket);

                        $app['EM']->persist($BasketElement);

                        $Basket->addBasketElement($BasketElement);
                    }
                    $app['EM']->flush();
                }

                $app['EM']->refresh($Basket);

                if (!$Basket->getValidation()) {
                    $Validation = new \Entities\ValidationSession();
                    $Validation->setInitiator($app['phraseanet.user']);
                    $Validation->setBasket($Basket);

                    $duration = (int) $request->request->get('duration');

                    if ($duration > 0) {
                        $date = new \DateTime('+' . $duration . ' day' . ($duration > 1 ? 's' : ''));
                        $Validation->setExpires($date);
                    }

                    $Basket->setValidation($Validation);
                    $app['EM']->persist($Validation);
                } else {
                    $Validation = $Basket->getValidation();
                }

                $found = false;
                foreach ($participants as $key => $participant) {
                    if ($participant['usr_id'] == $app['phraseanet.user']->get_id()) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $participants[$app['phraseanet.user']->get_id()] = array(
                        'see_others' => 1,
                        'usr_id'     => $app['phraseanet.user']->get_id(),
                        'agree'      => 0,
                        'HD'         => 0
                    );
                }

                foreach ($participants as $key => $participant) {
                    foreach (array('see_others', 'usr_id', 'agree', 'HD') as $mandatoryparam) {
                        if (!array_key_exists($mandatoryparam, $participant))
                            throw new ControllerException(sprintf(_('Missing mandatory parameter %s'), $mandatoryparam));
                    }

                    try {
                        $participant_user = \User_Adapter::getInstance($participant['usr_id'], $app);
                    } catch (\Exception $e) {
                        throw new ControllerException(sprintf(_('Unknown user %d'), $receiver['usr_id']));
                    }

                    try {
                        $Participant = $Validation->getParticipant($participant_user, $app);
                        continue;
                    } catch (\Exception_NotFound $e) {

                    }

                    $Participant = new \Entities\ValidationParticipant();
                    $Participant->setUser($participant_user);
                    $Participant->setSession($Validation);

                    $Participant->setCanAgree($participant['agree']);
                    $Participant->setCanSeeOthers($participant['see_others']);

                    $app['EM']->persist($Participant);

                    foreach ($Basket->getElements() as $BasketElement) {
                        $ValidationData = new \Entities\ValidationData();
                        $ValidationData->setParticipant($Participant);
                        $ValidationData->setBasketElement($BasketElement);
                        $BasketElement->addValidationData($ValidationData);

                        if ($participant['HD']) {
                            $participant_user->ACL()->grant_hd_on(
                                $BasketElement->getRecord($app)
                                , $app['phraseanet.user']
                                , \ACL::GRANT_ACTION_VALIDATE
                            );
                        } else {
                            $participant_user->ACL()->grant_preview_on(
                                $BasketElement->getRecord($app)
                                , $app['phraseanet.user']
                                , \ACL::GRANT_ACTION_VALIDATE
                            );
                        }

                        $app['EM']->merge($BasketElement);
                        $app['EM']->persist($ValidationData);

                        $app['phraseanet.logger']($BasketElement->getRecord($app)->get_databox())
                            ->log($BasketElement->getRecord($app), \Session_Logger::EVENT_PUSH, $participant_user->get_id(), '');

                        $Participant->addValidationData($ValidationData);
                    }

                    $Participant = $app['EM']->merge($Participant);

                    $app['EM']->flush();

                    $url = $app['phraseanet.registry']->get('GV_ServerName')
                        . 'lightbox/index.php?LOG='
                        . \random::getUrlToken($app, \random::TYPE_VIEW, $participant_user->get_id(), null, $Basket->getId());

                    $params = array(
                        'from'       => $app['phraseanet.user']->get_id()
                        , 'from_email' => $app['phraseanet.user']->get_email()
                        , 'to'         => $participant_user->get_id()
                        , 'to_email'   => $participant_user->get_email()
                        , 'to_name'    => $participant_user->get_display_name()
                        , 'url'        => $url
                        , 'accuse'     => !!$request->request->get('recept', false)
                        , 'message'    => $request->request->get('message')
                        , 'ssel_id'    => $Basket->getId()
                    );

                    $app['events-manager']->trigger('__PUSH_VALIDATION__', $params);
                }

                $Basket = $app['EM']->merge($Basket);
                $Validation = $app['EM']->merge($Validation);

                $app['EM']->flush();

                $message = sprintf(
                    _('%1$d records have been sent for validation to %2$d users')
                    , count($pusher->get_elements())
                    , count($request->request->get('participants'))
                );

                $ret = array(
                    'success' => true,
                    'message' => $message
                );

                $app['EM']->commit();
            } catch (ControllerException $e) {
                $ret['message'] = $e->getMessage();
                $app['EM']->rollback();
            }

            return $app->json($ret);
        });

        $controllers->get('/user/{usr_id}/', function(Application $app, $usr_id) use ($userFormatter) {
            $datas = null;

            $request = $app['request'];

            $query = new \User_Query($app);

            $query->on_bases_where_i_am($app['phraseanet.user']->ACL(), array('canpush'));

            $query->in(array($usr_id));

            $result = $query->include_phantoms()
                    ->limit(0, 1)
                    ->execute()->get_results();

            if ($result) {
                foreach ($result as $user) {
                    $datas = $userFormatter($user);
                }
            }

            return $app->json($datas);
        })->assert('usr_id', '\d+');

        $controllers->get('/list/{list_id}/', function(Application $app, $list_id) use ($listFormatter) {
            $datas = null;

            $repository = $app['EM']->getRepository('\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($app, $app['phraseanet.user'], $list_id);

            if ($list) {
                $datas = $listFormatter($list);
            }

            return $app->json($datas);
        })->assert('list_id', '\d+');

        $controllers->post('/add-user/', function(Application $app, Request $request) use ($userFormatter) {
            $result = array('success' => false, 'message' => '', 'user'    => null);

            try {
                if (!$app['phraseanet.user']->ACL()->has_right('manageusers'))
                    throw new ControllerException(_('You are not allowed to add users'));

                if (!$request->request->get('firstname'))
                    throw new ControllerException(_('First name is required'));

                if (!$request->request->get('lastname'))
                    throw new ControllerException(_('Last name is required'));

                if (!$request->request->get('email'))
                    throw new ControllerException(_('Email is required'));

                if (!\Swift_Validate::email($request->request->get('email')))
                    throw new ControllerException(_('Email is invalid'));
            } catch (ControllerException $e) {
                $result['message'] = $e->getMessage();

                return $app->json($result);
            }

            $user = null;
            $email = $request->request->get('email');

            try {
                $usr_id = \User_Adapter::get_usr_id_from_email($app, $email);
                $user = \User_Adapter::getInstance($usr_id, $app);

                $result['message'] = _('User already exists');
                $result['success'] = true;
                $result['user'] = $userFormatter($user);
            } catch (\Exception $e) {

            }

            if (!$user instanceof \User_Adapter) {
                try {
                    $password = \random::generatePassword();

                    $user = \User_Adapter::create($app, $email, $password, $email, false);

                    $user->set_firstname($request->request->get('firstname'))
                        ->set_lastname($request->request->get('lastname'));

                    if ($request->request->get('company'))
                        $user->set_company($request->request->get('company'));
                    if ($request->request->get('job'))
                        $user->set_company($request->request->get('job'));
                    if ($request->request->get('form_geonameid'))
                        $user->set_geonameid($request->request->get('form_geonameid'));

                    $result['message'] = _('User successfully created');
                    $result['success'] = true;
                    $result['user'] = $userFormatter($user);
                } catch (\Exception $e) {
                    $result['message'] = _('Error while creating user');
                }
            }

            return $app->json($result);
        });

        $controllers->get('/add-user/', function(Application $app, Request $request) {
            $params = array('callback' => $request->query->get('callback'));

            return $app['twig']->render('prod/User/Add.html.twig', $params);
        });

        $controllers->get('/search-user/', function(Application $app) use ($userFormatter, $listFormatter) {
            $request = $app['request'];

            $query = new \User_Query($app);

            $query->on_bases_where_i_am($app['phraseanet.user']->ACL(), array('canpush'));

            $query->like(\User_Query::LIKE_FIRSTNAME, $request->query->get('query'))
                ->like(\User_Query::LIKE_LASTNAME, $request->query->get('query'))
                ->like(\User_Query::LIKE_LOGIN, $request->query->get('query'))
                ->like_match(\User_Query::LIKE_MATCH_OR);

            $result = $query->include_phantoms()
                    ->limit(0, 50)
                    ->execute()->get_results();

            $repository = $app['EM']->getRepository('\Entities\UsrList');

            $lists = $repository->findUserListLike($app['phraseanet.user'], $request->query->get('query'));

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

            return $app->json($datas);
        });

        $controllers->match('/edit-list/{list_id}/', function(Application $app, Request $request, $list_id) {

            $repository = $app['EM']->getRepository('\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($app, $app['phraseanet.user'], $list_id);

            $query = new \User_Query($app);

            $query->on_bases_where_i_am($app['phraseanet.user']->ACL(), array('canpush'));

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
                        $app['twig']->render('prod/actions/Feedback/ResultTable.html.twig', $params)
                );
            } else {
                return new Response(
                        $app['twig']->render('prod/actions/Feedback/list.html.twig', $params)
                );
            }
        })->assert('list_id', '\d+');

        return $controllers;
    }
}
