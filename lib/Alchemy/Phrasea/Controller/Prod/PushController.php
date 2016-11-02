<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Application\Helper\UserQueryAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\Exception as ControllerException;
use Alchemy\Phrasea\Core\Event\PushEvent;
use Alchemy\Phrasea\Core\Event\ValidationEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrList;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Alchemy\Phrasea\Model\Entities\ValidationSession;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use RandomLib\Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PushController extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use EntityManagerAware;
    use UserQueryAware;

    public function postFormAction(Request $request)
    {
        return $this->renderPushTemplate($request, 'Push');
    }

    public function validateFormAction(Request $request)
    {
        return $this->renderPushTemplate($request, 'Feedback');
    }

    public function sendAction(Request $request)
    {
        $ret = [
            'success' => false,
            'message' => $this->app->trans('Unable to send the documents')
        ];

        try {
            $pusher = new RecordHelper\Push($this->app, $request);

            $push_name = $request->request->get(
                'name',
                $this->app->trans('Push from %user%', [
                    '%user%' => $this->getAuthenticatedUser()->getDisplayName(),
                ])
            );
            $push_description = $request->request->get('push_description');

            $receivers = $request->request->get('participants');

            if (!is_array($receivers) || empty($receivers)) {
                throw new ControllerException($this->app->trans('No receivers specified'));
            }

            if (!is_array($pusher->get_elements()) || empty($pusher->get_elements())) {
                throw new ControllerException($this->app->trans('No elements to push'));
            }

            $manager = $this->getEntityManager();
            foreach ($receivers as $receiver) {
                try {
                    /** @var User $user_receiver */
                    $user_receiver = $this->getUserRepository()->find($receiver['usr_id']);
                } catch (\Exception $e) {
                    throw new ControllerException(
                        $this->app->trans('Unknown user %user_id%', ['%user_id%' => $receiver['usr_id']])
                    );
                }

                $Basket = new Basket();
                $Basket->setName($push_name);
                $Basket->setDescription($push_description);
                $Basket->setUser($user_receiver);
                $Basket->setPusher($this->getAuthenticatedUser());
                $Basket->markUnread();
                
                $manager->persist($Basket);

                foreach ($pusher->get_elements() as $element) {
                    $basketElement = new BasketElement();
                    $basketElement->setRecord($element);
                    $basketElement->setBasket($Basket);

                    $manager->persist($basketElement);

                    $Basket->addElement($basketElement);

                    if ($receiver['HD']) {
                        $this->getAclForUser($user_receiver)->grant_hd_on(
                            $basketElement->getRecord($this->app),
                            $this->getAuthenticatedUser(),
                            \ACL::GRANT_ACTION_PUSH
                        );
                    } else {
                        $this->getAclForUser($user_receiver)->grant_preview_on(
                            $basketElement->getRecord($this->app),
                            $this->getAuthenticatedUser(),
                            \ACL::GRANT_ACTION_PUSH
                        );
                    }

                    $this->getDataboxLogger($element->getDatabox())->log(
                        $element,
                        \Session_Logger::EVENT_VALIDATE,
                        $user_receiver->getId(),
                        ''
                    );
                }

                $manager->flush();

                $arguments = [
                    'basket' => $Basket->getId(),
                ];

                if (!$this->getConf()->get(['registry', 'actions', 'enable-push-authentication'])
                    || !$request->get('force_authentication')
                ) {
                    $arguments['LOG'] = $this->getTokenManipulator()->createBasketAccessToken($Basket, $user_receiver)->getValue();
                }

                $url = $this->app->url('lightbox_compare', $arguments);


                $receipt = $request->get('recept') ? $this->getAuthenticatedUser()->getEmail() : '';
                $this->dispatch(
                    PhraseaEvents::BASKET_PUSH,
                    new PushEvent($Basket, $request->request->get('message'), $url, $receipt)
                );
            }


            $manager->flush();

            $message = $this->app->trans(
                '%quantity_records% records have been sent to %quantity_users% users',
                [
                    '%quantity_records%' => count($pusher->get_elements()),
                    '%quantity_users%'   => count($receivers),
                ]
            );

            $ret = [
                'success' => true,
                'message' => $message
            ];
        } catch (ControllerException $e) {
            $ret['message'] = $e->getMessage() . $e->getFile() . $e->getLine();
        }

        return $this->app->json($ret);
    }

    public function validateAction(Request $request)
    {
        $ret = [
            'success' => false,
            'message' => $this->app->trans('Unable to send the documents')
        ];

        $manager = $this->getEntityManager();
        $manager->beginTransaction();

        try {
            $pusher = $this->getPushFromRequest($request);

            $validation_name = $request->request->get('name', $this->app->trans(
                'Validation from %user%', [
                '%user%' => $this->getAuthenticatedUser()->getDisplayName(),
            ]));
            $validation_description = $request->request->get('validation_description');

            $participants = $request->request->get('participants');

            if (!is_array($participants) || empty($participants)) {
                throw new ControllerException($this->app->trans('No participants specified'));
            }

            if (!is_array($pusher->get_elements()) || empty($pusher->get_elements())) {
                throw new ControllerException($this->app->trans('No elements to validate'));
            }

            if ($pusher->is_basket()) {
                $basket = $pusher->get_original_basket();
            } else {
                $basket = new Basket();
                $basket->setName($validation_name);
                $basket->setDescription($validation_description);
                $basket->setUser($this->getAuthenticatedUser());
                $basket->markUnread();

                $manager->persist($basket);

                foreach ($pusher->get_elements() as $element) {
                    $basketElement = new BasketElement();
                    $basketElement->setRecord($element);
                    $basketElement->setBasket($basket);

                    $manager->persist($basketElement);

                    $basket->addElement($basketElement);
                }
                $manager->flush();
            }

            $manager->refresh($basket);

            if (!$basket->getValidation()) {
                $Validation = new ValidationSession();
                $Validation->setInitiator($this->getAuthenticatedUser());
                $Validation->setBasket($basket);

                $duration = (int)$request->request->get('duration');

                if ($duration > 0) {
                    $date = new \DateTime('+' . $duration . ' day' . ($duration > 1 ? 's' : ''));
                    $Validation->setExpires($date);
                }

                $basket->setValidation($Validation);
                $manager->persist($Validation);
            } else {
                $Validation = $basket->getValidation();
            }

            $found = false;
            foreach ($participants as $participant) {
                if ($participant['usr_id'] === $this->getAuthenticatedUser()->getId()) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $participants[] = [
                    'see_others' => 1,
                    'usr_id'     => $this->getAuthenticatedUser()->getId(),
                    'agree'      => 0,
                    'HD'         => 0,
                ];
            }

            foreach ($participants as $key => $participant) {
                foreach (['see_others', 'usr_id', 'agree', 'HD'] as $mandatoryParam) {
                    if (!array_key_exists($mandatoryParam, $participant)) {
                        throw new ControllerException(
                            $this->app->trans('Missing mandatory parameter %parameter%', ['%parameter%' => $mandatoryParam])
                        );
                    }
                }

                try {
                    /** @var User $participantUser */
                    $participantUser = $this->getUserRepository()->find($participant['usr_id']);
                } catch (\Exception $e) {
                    throw new ControllerException(
                        $this->app->trans('Unknown user %usr_id%', ['%usr_id%' => $participant['usr_id']])
                    );
                }

                try {
                    $Validation->getParticipant($participantUser);
                    continue;
                } catch (NotFoundHttpException $e) {

                }

                $validationParticipant = new ValidationParticipant();
                $validationParticipant->setUser($participantUser);
                $validationParticipant->setSession($Validation);

                $validationParticipant->setCanAgree($participant['agree']);
                $validationParticipant->setCanSeeOthers($participant['see_others']);

                $manager->persist($validationParticipant);

                foreach ($basket->getElements() as $basketElement) {
                    $validationData = new ValidationData();
                    $validationData->setParticipant($validationParticipant);
                    $validationData->setBasketElement($basketElement);
                    $basketElement->addValidationData($validationData);

                    if ($participant['HD']) {
                        $this->getAclForUser($participantUser)->grant_hd_on(
                            $basketElement->getRecord($this->app),
                            $this->getAuthenticatedUser(),
                            \ACL::GRANT_ACTION_VALIDATE
                        );
                    } else {
                        $this->getAclForUser($participantUser)->grant_preview_on(
                            $basketElement->getRecord($this->app),
                            $this->getAuthenticatedUser(),
                            \ACL::GRANT_ACTION_VALIDATE
                        );
                    }

                    $manager->merge($basketElement);
                    $manager->persist($validationData);

                    $this->getDataboxLogger($basketElement->getRecord($this->app)->getDatabox())->log(
                        $basketElement->getRecord($this->app),
                        \Session_Logger::EVENT_PUSH,
                        $participantUser->getId(),
                        ''
                    );

                    $validationParticipant->addData($validationData);
                }

                $validationParticipant = $manager->merge($validationParticipant);

                $manager->flush();

                $arguments = [
                    'basket' => $basket->getId(),
                ];

                if (!$this->getConf()->get(['registry', 'actions', 'enable-push-authentication'])
                    || !$request->get('force_authentication')
                ) {
                    $arguments['LOG'] = $this->getTokenManipulator()->createBasketAccessToken($basket, $participantUser)->getValue();
                }

                $url = $this->app->url('lightbox_validation', $arguments);


                $receipt = $request->get('recept') ? $this->getAuthenticatedUser()->getEmail() : '';

                $this->dispatch(
                    PhraseaEvents::VALIDATION_CREATE,
                    new ValidationEvent(
                        $validationParticipant,
                        $basket,
                        $url,
                        $request->request->get('message'),
                        $receipt,
                        (int)$request->request->get('duration')
                    )
                );
            }

            $manager->merge($basket);
            $manager->merge($Validation);
            $manager->flush();

            $message = $this->app->trans(
                '%quantity_records% records have been sent for validation to %quantity_users% users',
                [
                    '%quantity_records%' => count($pusher->get_elements()),
                    '%quantity_users%'   => count($request->request->get('participants')),
                ]
            );

            $ret = [
                'success' => true,
                'message' => $message,
            ];

            $manager->commit();
        } catch (ControllerException $e) {
            $ret['message'] = $e->getMessage();
            $manager->rollback();
        }

        return $this->app->json($ret);
    }

    public function getUserAction($usr_id)
    {
        $data = null;

        $query = $this->createUserQuery();
        $query->on_bases_where_i_am($this->getAclForUser($this->getAuthenticatedUser()), [\ACL::CANPUSH]);

        $query->in([$usr_id]);

        $result = $query->include_phantoms()
            ->limit(0, 1)
            ->execute()->get_results();

        if ($result) {
            foreach ($result as $user) {
                $data = $this->formatUser($user);
            }
        }

        return $this->app->json($data);
    }

    public function getListAction($list_id)
    {
        $data = null;

        $repository = $this->getUserListRepository();
        $list = $repository->findUserListByUserAndId($this->getAuthenticatedUser(), $list_id);

        if ($list) {
            $data = $this->formatUserList($list);
        }

        return $this->app->json($data);
    }

    public function addUserAction(Request $request)
    {
        $result = ['success' => false, 'message' => '', 'user'    => null];

        try {
            if (!$this->getAclForUser($this->getAuthenticatedUser())->has_right(\ACL::CANADMIN))
                throw new ControllerException($this->app->trans('You are not allowed to add users'));

            if (!$request->request->get('firstname'))
                throw new ControllerException($this->app->trans('First name is required'));

            if (!$request->request->get('lastname'))
                throw new ControllerException($this->app->trans('Last name is required'));

            if (!$request->request->get('email'))
                throw new ControllerException($this->app->trans('Email is required'));

            if (!\Swift_Validate::email($request->request->get('email')))
                throw new ControllerException($this->app->trans('Email is invalid'));
        } catch (ControllerException $e) {
            $result['message'] = $e->getMessage();

            return $this->app->json($result);
        }

        $user = null;
        $email = $request->request->get('email');

        if (null !== $user = $this->getUserRepository()->findByEmail($email)) {
            $result['message'] = $this->app->trans('User already exists');
            $result['success'] = true;
            $result['user'] = $this->formatUser($user);

            return $this->app->json($result);
        }

        try {
            $password = $this->getRandomGenerator()->generateString(128);

            $user = $this->getUserManipulator()->createUser($email, $password, $email);

            $user
                ->setFirstName($request->request->get('firstname'))
                ->setLastName($request->request->get('lastname'))
            ;

            if ($request->request->get('company')) {
                $user->setCompany($request->request->get('company'));
            }
            if ($request->request->get('job')) {
                $user->setCompany($request->request->get('job'));
            }
            if ($request->request->get('form_geonameid')) {
                $this->getUserManipulator()->setGeonameId($user, $request->request->get('form_geonameid'));
            }

            $result['message'] = $this->app->trans('User successfully created');
            $result['success'] = true;
            $result['user'] = $this->formatUser($user);
        } catch (\Exception $e) {
            $result['message'] = $this->app->trans('Error while creating user');
        }

        return $this->app->json($result);
    }

    public function getAddUserFormAction(Request $request)
    {
        $params = ['callback' => $request->query->get('callback')];

        return $this->render('prod/User/Add.html.twig', $params);
    }

    public function searchUserAction(Request $request)
    {
        $query = $this->createUserQuery();
        $query->on_bases_where_i_am($this->getAclForUser($this->getAuthenticatedUser()), [\ACL::CANPUSH]);
        $query
            ->like(\User_Query::LIKE_FIRSTNAME, $request->query->get('query'))
            ->like(\User_Query::LIKE_LASTNAME, $request->query->get('query'))
            ->like(\User_Query::LIKE_LOGIN, $request->query->get('query'))
            ->like_match(\User_Query::LIKE_MATCH_OR);

        $result = $query
            ->include_phantoms()
            ->limit(0, 50)
            ->execute()->get_results();

        $repository = $this->getUserListRepository();
        $lists = $repository->findUserListLike($this->getAuthenticatedUser(), $request->query->get('query'));

        $data = [];

        if ($lists) {
            foreach ($lists as $list) {
                $data[] = $this->formatUserList($list);
            }
        }

        if ($result) {
            foreach ($result as $user) {
                $data[] = $this->formatUser($user);
            }
        }

        return $this->app->json($data);
    }

    public function editListAction(Request $request, $list_id)
    {
        $repository = $this->getUserListRepository();
        $list = $repository->findUserListByUserAndId($this->getAuthenticatedUser(), $list_id);

        $query = $this->createUserQuery();
        $query->on_bases_where_i_am($this->getAclForUser($this->getAuthenticatedUser()), [\ACL::CANPUSH]);

        if ($request->get('query')) {
            $query
                ->like($request->get('like_field'), $request->get('query'))
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

        $results = $query
            ->include_phantoms()
            ->limit($offset_start, $perPage)
            ->execute()->get_results();

        $params = [
            'query'   => $query,
            'results' => $results,
            'list'    => $list,
            'sort'    => $sort,
            'ord'     => $ord,
        ];

        if ($request->get('type') === 'fragment') {
            return new Response(
                $this->render('prod/actions/Feedback/ResultTable.html.twig', $params)
            );
        }

        return new Response(
            $this->render('prod/actions/Feedback/list.html.twig', $params)
        );
    }

    private function formatUser(User $user)
    {
        $subtitle = array_filter([$user->getJob(), $user->getCompany()]);

        return [
            'type'         => 'USER',
            'usr_id'       => $user->getId(),
            'firstname'    => $user->getFirstName(),
            'lastname'     => $user->getLastName(),
            'email'        => $user->getEmail(),
            'display_name' => $user->getDisplayName(),
            'subtitle'     => implode(', ', $subtitle),
        ];
    }

    private function formatUserList(UsrList $list)
    {
        $entries = [];

        foreach ($list->getEntries() as $entry) {
            $entries[] = [
                'Id'   => $entry->getId(),
                'User' => $this->formatUser($entry->getUser()),
            ];
        }

        return [
            'type'    => 'LIST',
            'list_id' => $list->getId(),
            'name'    => $list->getName(),
            'length'  => count($entries),
            'entries' => $entries,
        ];
    }

    /**
     * @param array|\record_adapter[] $selection
     * @return User[]
     */
    private function getUsersInSelectionExtractor($selection)
    {
        $users = new ArrayCollection();

        foreach ($selection as $record) {
            foreach ($record->get_caption()->get_fields() as $caption_field) {
                foreach ($caption_field->get_values() as $value) {
                    if (!$value->getVocabularyType())
                        continue;

                    if ($value->getVocabularyType()->getType() !== 'User')
                        continue;

                    $user = $value->getResource();

                    $users->set($user->getId(), $user);
                }
            }
        }

        return $users;
    }

    /**
     * @return UsrListRepository
     */
    private function getUserListRepository()
    {
        return $this->app['repo.usr-lists'];
    }

    /**
     * @param Request $request
     * @param         $context
     * @return string
     */
    public function renderPushTemplate(Request $request, $context)
    {
        $push = $this->getPushFromRequest($request);

        $repository = $this->getUserListRepository();
        $recommendedUsers = $this->getUsersInSelectionExtractor($push->get_elements());

        return $this->render(
            'prod/actions/Push.html.twig',
            [
                'push'             => $push,
                'message'          => '',
                'lists'            => $repository->findUserLists($this->getAuthenticatedUser()),
                'context'          => $context,
                'RecommendedUsers' => $recommendedUsers,
            ]
        );
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->app['repo.users'];
    }

    /**
     * @return TokenManipulator
     */
    private function getTokenManipulator()
    {
        return $this->app['manipulator.token'];
    }

    /**
     * @param Request $request
     * @return RecordHelper\Push
     */
    private function getPushFromRequest(Request $request)
    {
        return new RecordHelper\Push($this->app, $request);
    }

    /**
     * @return UserManipulator
     */
    private function getUserManipulator()
    {
        return $this->app['manipulator.user'];
    }

    /**
     * @return Generator
     */
    private function getRandomGenerator()
    {
        return $this->app['random.medium'];
    }
}
