<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrList;
use Alchemy\Phrasea\Model\Entities\UsrListEntry;
use Alchemy\Phrasea\Model\Entities\ValidationSession;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Alchemy\Phrasea\Controller\Exception as ControllerException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Push implements ControllerProviderInterface
{
    protected function getUserFormatter(Application $app)
    {
        return function (User $user) use ($app) {
            $subtitle = array_filter([$user->getJob(), $user->getCompany()]);

            return [
                'type'         => 'USER',
                'usr_id'       => $user->getId(),
                'firstname'    => $user->getFirstName(),
                'lastname'     => $user->getLastName(),
                'email'        => $user->getEmail(),
                'display_name' => $user->getDisplayName($app['translator']),
                'subtitle'     => implode(', ', $subtitle),
            ];
        };
    }

    protected function getListFormatter($app)
    {
        $userFormatter = $this->getUserFormatter($app);

        return function (UsrList $List) use ($userFormatter, $app) {
            $entries = [];

            foreach ($List->getEntries() as $entry) {
                /* @var $entry UsrListEntry */
                $entries[] = [
                    'Id'   => $entry->getId(),
                    'User' => $userFormatter($entry->getUser())
                ];
            }

            return [
                'type'    => 'LIST',
                'list_id' => $List->getId(),
                'name'    => $List->getName(),
                'length'  => count($entries),
                'entries' => $entries,
            ];
        };
    }

    protected function getUsersInSelectionExtractor()
    {
        return function (array $selection) {
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

                        $Users->set($user->getId(), $user);
                    }
                }
            }

            return $Users;
        };
    }

    public function connect(Application $app)
    {
        $app['controller.prod.push'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireRight('push');
        });

        $userFormatter = $this->getUserFormatter($app);

        $listFormatter = $this->getListFormatter($app);

        $userSelection = $this->getUsersInSelectionExtractor();

        $controllers->post('/sendform/', function (Application $app) use ($userSelection) {
            $push = new RecordHelper\Push($app, $app['request']);

            $repository = $app['EM']->getRepository('Phraseanet:UsrList');

            $RecommendedUsers = $userSelection($push->get_elements());

            $params = [
                'push'             => $push,
                'message'          => '',
                'lists'            => $repository->findUserLists($app['authentication']->getUser()),
                'context'          => 'Push',
                'RecommendedUsers' => $RecommendedUsers
            ];

            return $app['twig']->render('prod/actions/Push.html.twig', $params);
        });

        $controllers->post('/validateform/', function (Application $app) use ($userSelection) {
            $push = new RecordHelper\Push($app, $app['request']);

            $repository = $app['EM']->getRepository('Phraseanet:UsrList');

            $RecommendedUsers = $userSelection($push->get_elements());

            $params = [
                'push'             => $push,
                'message'          => '',
                'lists'            => $repository->findUserLists($app['authentication']->getUser()),
                'context'          => 'Feedback',
                'RecommendedUsers' => $RecommendedUsers
            ];

            return $app['twig']->render('prod/actions/Push.html.twig', $params);
        });

        $controllers->post('/send/', function (Application $app) {
            $request = $app['request'];

            $ret = [
                'success' => false,
                'message' => $app->trans('Unable to send the documents')
            ];

            try {
                $pusher = new RecordHelper\Push($app, $app['request']);

                $push_name = $request->request->get('name', $app->trans('Push from %user%', ['%user%' => $app['authentication']->getUser()->getDisplayName($app['translator'])]));
                $push_description = $request->request->get('push_description');

                $receivers = $request->request->get('participants');

                if (!is_array($receivers) || count($receivers) === 0) {
                    throw new ControllerException($app->trans('No receivers specified'));
                }

                if (!is_array($pusher->get_elements()) || count($pusher->get_elements()) === 0) {
                    throw new ControllerException($app->trans('No elements to push'));
                }

                foreach ($receivers as $receiver) {
                    try {
                        $user_receiver = $app['manipulator.user']->getRepository()->find($receiver['usr_id']);
                    } catch (\Exception $e) {
                        throw new ControllerException($app->trans('Unknown user %user_id%', ['%user_id%' => $receiver['usr_id']]));
                    }

                    $Basket = new Basket();
                    $Basket->setName($push_name);
                    $Basket->setDescription($push_description);
                    $Basket->setUser($user_receiver);
                    $Basket->setPusher($app['authentication']->getUser());
                    $Basket->setIsRead(false);

                    $app['EM']->persist($Basket);

                    foreach ($pusher->get_elements() as $element) {
                        $BasketElement = new BasketElement();
                        $BasketElement->setRecord($element);
                        $BasketElement->setBasket($Basket);

                        $app['EM']->persist($BasketElement);

                        $Basket->addElement($BasketElement);

                        if ($receiver['HD']) {
                            $app['acl']->get($user_receiver)->grant_hd_on(
                                $BasketElement->getRecord($app)
                                , $app['authentication']->getUser()
                                , \ACL::GRANT_ACTION_PUSH
                            );
                        } else {
                            $app['acl']->get($user_receiver)->grant_preview_on(
                                $BasketElement->getRecord($app)
                                , $app['authentication']->getUser()
                                , \ACL::GRANT_ACTION_PUSH
                            );
                        }
                    }

                    $app['EM']->flush();

                    $url = $app->url('lightbox_compare', [
                        'basket' => $Basket->getId(),
                        'LOG' => $app['tokens']->getUrlToken(
                            \random::TYPE_VIEW,
                            $user_receiver->getId(),
                            null,
                            $Basket->getId()
                        )
                    ]);

                    $receipt = $request->get('recept') ? $app['authentication']->getUser()->getEmail() : '';

                    $params = [
                        'from'       => $app['authentication']->getUser()->getId(),
                        'from_email' => $app['authentication']->getUser()->getEmail(),
                        'to'         => $user_receiver->getId(),
                        'to_email'   => $user_receiver->getEmail(),
                        'to_name'    => $user_receiver->getDisplayName($app['translator']),
                        'url'        => $url,
                        'accuse'     => $receipt,
                        'message'    => $request->request->get('message'),
                        'ssel_id'    => $Basket->getId(),
                    ];

                    $app['events-manager']->trigger('__PUSH_DATAS__', $params);
                }

                $app['phraseanet.logger']($BasketElement->getRecord($app)->get_databox())
                    ->log($BasketElement->getRecord($app), \Session_Logger::EVENT_VALIDATE, $user_receiver->getId(), '');

                $app['EM']->flush();

                $message = $app->trans('%quantity_records% records have been sent to %quantity_users% users', [
                    '%quantity_records%' => count($pusher->get_elements()),
                    '%quantity_users%'   => count($receivers),
                ]);

                $ret = [
                    'success' => true,
                    'message' => $message
                ];
            } catch (ControllerException $e) {
                $ret['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
            }

            return $app->json($ret);
        })->bind('prod_push_send');

        $controllers->post('/validate/', function (Application $app) {
            $request = $app['request'];

            $ret = [
                'success' => false,
                'message' => $app->trans('Unable to send the documents')
            ];

            $app['EM']->beginTransaction();

            try {
                $pusher = new RecordHelper\Push($app, $app['request']);

                $validation_name = $request->request->get('name', $app->trans('Validation from %user%', ['%user%' => $app['authentication']->getUser()->getDisplayName($app['translator'])]));
                $validation_description = $request->request->get('validation_description');

                $participants = $request->request->get('participants');

                if (!is_array($participants) || count($participants) === 0) {
                    throw new ControllerException($app->trans('No participants specified'));
                }

                if (!is_array($pusher->get_elements()) || count($pusher->get_elements()) === 0) {
                    throw new ControllerException($app->trans('No elements to validate'));
                }

                if ($pusher->is_basket()) {
                    $Basket = $pusher->get_original_basket();
                } else {
                    $Basket = new Basket();
                    $Basket->setName($validation_name);
                    $Basket->setDescription($validation_description);
                    $Basket->setUser($app['authentication']->getUser());
                    $Basket->setIsRead(false);

                    $app['EM']->persist($Basket);

                    foreach ($pusher->get_elements() as $element) {
                        $BasketElement = new BasketElement();
                        $BasketElement->setRecord($element);
                        $BasketElement->setBasket($Basket);

                        $app['EM']->persist($BasketElement);

                        $Basket->addElement($BasketElement);
                    }
                    $app['EM']->flush();
                }

                $app['EM']->refresh($Basket);

                if (!$Basket->getValidation()) {
                    $Validation = new ValidationSession();
                    $Validation->setInitiator($app['authentication']->getUser());
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
                foreach ($participants as $participant) {
                    if ($participant['usr_id'] === $app['authentication']->getUser()->getId()) {
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    $participants[] = [
                        'see_others' => 1,
                        'usr_id'     => $app['authentication']->getUser()->getId(),
                        'agree'      => 0,
                        'HD'         => 0
                    ];
                }

                foreach ($participants as $key => $participant) {
                    foreach (['see_others', 'usr_id', 'agree', 'HD'] as $mandatoryparam) {
                        if (!array_key_exists($mandatoryparam, $participant))
                            throw new ControllerException($app->trans('Missing mandatory parameter %parameter%', ['%parameter%' => $mandatoryparam]));
                    }

                    try {
                        $participant_user = $app['manipulator.user']->getRepository()->find($participant['usr_id']);
                    } catch (\Exception $e) {
                        throw new ControllerException($app->trans('Unknown user %usr_id%', ['%usr_id%' => $participant['usr_id']]));
                    }

                    try {
                        $Participant = $Validation->getParticipant($participant_user, $app);
                        continue;
                    } catch (NotFoundHttpException $e) {

                    }

                    $Participant = new ValidationParticipant();
                    $Participant->setUser($participant_user);
                    $Participant->setSession($Validation);

                    $Participant->setCanAgree($participant['agree']);
                    $Participant->setCanSeeOthers($participant['see_others']);

                    $app['EM']->persist($Participant);

                    foreach ($Basket->getElements() as $BasketElement) {
                        $ValidationData = new ValidationData();
                        $ValidationData->setParticipant($Participant);
                        $ValidationData->setBasketElement($BasketElement);
                        $BasketElement->addValidationData($ValidationData);

                        if ($participant['HD']) {
                            $app['acl']->get($participant_user)->grant_hd_on(
                                $BasketElement->getRecord($app)
                                , $app['authentication']->getUser()
                                , \ACL::GRANT_ACTION_VALIDATE
                            );
                        } else {
                            $app['acl']->get($participant_user)->grant_preview_on(
                                $BasketElement->getRecord($app)
                                , $app['authentication']->getUser()
                                , \ACL::GRANT_ACTION_VALIDATE
                            );
                        }

                        $app['EM']->merge($BasketElement);
                        $app['EM']->persist($ValidationData);

                        $app['phraseanet.logger']($BasketElement->getRecord($app)->get_databox())
                            ->log($BasketElement->getRecord($app), \Session_Logger::EVENT_PUSH, $participant_user->getId(), '');

                        $Participant->addData($ValidationData);
                    }

                    $Participant = $app['EM']->merge($Participant);

                    $app['EM']->flush();

                    $url = $app->url('lightbox_validation', [
                        'basket' => $Basket->getId(),
                        'LOG' => $app['tokens']->getUrlToken(
                            \random::TYPE_VALIDATE,
                            $participant_user->getId(),
                            null,
                            $Basket->getId()
                        )
                    ]);

                    $receipt = $request->get('recept') ? $app['authentication']->getUser()->getEmail() : '';

                    $params = [
                        'from'       => $app['authentication']->getUser()->getId(),
                        'from_email' => $app['authentication']->getUser()->getEmail(),
                        'to'         => $participant_user->getId(),
                        'to_email'   => $participant_user->getEmail(),
                        'to_name'    => $participant_user->getDisplayName($app['translator']),
                        'url'        => $url,
                        'accuse'     => $receipt,
                        'message'    => $request->request->get('message'),
                        'ssel_id'    => $Basket->getId(),
                        'duration'   => (int) $request->request->get('duration'),
                    ];

                    $app['events-manager']->trigger('__PUSH_VALIDATION__', $params);
                }

                $Basket = $app['EM']->merge($Basket);
                $Validation = $app['EM']->merge($Validation);

                $app['EM']->flush();

                $message = $app->trans('%quantity_records% records have been sent for validation to %quantity_users% users', [
                    '%quantity_records%' => count($pusher->get_elements()),
                    '%quantity_users%'   => count($request->request->get('participants')),
                ]);

                $ret = [
                    'success' => true,
                    'message' => $message
                ];

                $app['EM']->commit();
            } catch (ControllerException $e) {
                $ret['message'] = $e->getMessage();
                $app['EM']->rollback();
            }

            return $app->json($ret);
        })->bind('prod_push_validate');

        $controllers->get('/user/{usr_id}/', function (Application $app, $usr_id) use ($userFormatter) {
            $datas = null;

            $request = $app['request'];

            $query = new \User_Query($app);

            $query->on_bases_where_i_am($app['acl']->get($app['authentication']->getUser()), ['canpush']);

            $query->in([$usr_id]);

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

        $controllers->get('/list/{list_id}/', function (Application $app, $list_id) use ($listFormatter) {
            $datas = null;

            $repository = $app['EM']->getRepository('Phraseanet:UsrList');

            $list = $repository->findUserListByUserAndId($app['authentication']->getUser(), $list_id);

            if ($list) {
                $datas = $listFormatter($list);
            }

            return $app->json($datas);
        })
            ->bind('prod_push_lists_list')
            ->assert('list_id', '\d+');

        $controllers->post('/add-user/', function (Application $app, Request $request) use ($userFormatter) {
            $result = ['success' => false, 'message' => '', 'user'    => null];

            try {
                if (!$app['acl']->get($app['authentication']->getUser())->has_right('manageusers'))
                    throw new ControllerException($app->trans('You are not allowed to add users'));

                if (!$request->request->get('firstname'))
                    throw new ControllerException($app->trans('First name is required'));

                if (!$request->request->get('lastname'))
                    throw new ControllerException($app->trans('Last name is required'));

                if (!$request->request->get('email'))
                    throw new ControllerException($app->trans('Email is required'));

                if (!\Swift_Validate::email($request->request->get('email')))
                    throw new ControllerException($app->trans('Email is invalid'));
            } catch (ControllerException $e) {
                $result['message'] = $e->getMessage();

                return $app->json($result);
            }

            $user = null;
            $email = $request->request->get('email');

            try {
                $user = $app['manipulator.user']->getRepository()->findByEmail($email);

                $result['message'] = $app->trans('User already exists');
                $result['success'] = true;
                $result['user'] = $userFormatter($user);
            } catch (\Exception $e) {

            }

            if (!$user instanceof User) {
                try {
                    $password = \random::generatePassword();

                    $user = $app['manipulator.user']->getRepository()->createUser($email, $password, $email);

                    $user->setFirstName($request->request->get('firstname'))
                        ->setLastName($request->request->get('lastname'));

                    if ($request->request->get('company'))
                        $user->setCompany($request->request->get('company'));
                    if ($request->request->get('job'))
                        $user->setCompany($request->request->get('job'));
                    if ($request->request->get('form_geonameid'))
                        $app['manipulator.user']->setGeonameId($user, $request->request->get('form_geonameid'));

                    $result['message'] = $app->trans('User successfully created');
                    $result['success'] = true;
                    $result['user'] = $userFormatter($user);
                } catch (\Exception $e) {
                    $result['message'] = $app->trans('Error while creating user');
                }
            }

            return $app->json($result);
        })->bind('prod_push_do_add_user');

        $controllers->get('/add-user/', function (Application $app, Request $request) {
            $params = ['callback' => $request->query->get('callback')];

            return $app['twig']->render('prod/User/Add.html.twig', $params);
        })->bind('prod_push_add_user');

        $controllers->get('/search-user/', function (Application $app) use ($userFormatter, $listFormatter) {
            $request = $app['request'];

            $query = new \User_Query($app);

            $query->on_bases_where_i_am($app['acl']->get($app['authentication']->getUser()), ['canpush']);

            $query->like(\User_Query::LIKE_FIRSTNAME, $request->query->get('query'))
                ->like(\User_Query::LIKE_LASTNAME, $request->query->get('query'))
                ->like(\User_Query::LIKE_LOGIN, $request->query->get('query'))
                ->like_match(\User_Query::LIKE_MATCH_OR);

            $result = $query->include_phantoms()
                    ->limit(0, 50)
                    ->execute()->get_results();

            $repository = $app['EM']->getRepository('Phraseanet:UsrList');

            $lists = $repository->findUserListLike($app['authentication']->getUser(), $request->query->get('query'));

            $datas = [];

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

        $controllers->match('/edit-list/{list_id}/', function (Application $app, Request $request, $list_id) {

            $repository = $app['EM']->getRepository('Phraseanet:UsrList');

            $list = $repository->findUserListByUserAndId($app['authentication']->getUser(), $list_id);

            $query = new \User_Query($app);

            $query->on_bases_where_i_am($app['acl']->get($app['authentication']->getUser()), ['canpush']);

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

            $params = [
                'query'   => $query
                , 'results' => $results
                , 'list'    => $list
                , 'sort'    => $sort
                , 'ord'     => $ord
            ];

            if ($request->get('type') === 'fragment') {
                return new Response(
                        $app['twig']->render('prod/actions/Feedback/ResultTable.html.twig', $params)
                );
            } else {
                return new Response(
                        $app['twig']->render('prod/actions/Feedback/list.html.twig', $params)
                );
            }
        })
            ->bind('prod_push_list_edit')
            ->assert('list_id', '\d+');

        return $controllers;
    }
}
