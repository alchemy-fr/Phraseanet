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

use ACL;
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
use Alchemy\Phrasea\Model\Repositories\BasketRepository;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrListRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use RandomLib\Generator;
use record_adapter;
use Session_Logger;
use Swift_Validate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use User_Query;

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


    /** ----------------------------------------------------------------------------------
     * a simple push is made by the current user to many "receivers" (=participants)
     *
     * this is the same code as "validation" request, except here we don't create a validation session
     *
     *
     * @param Request $request
     * @return JsonResponse
     */
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
                } catch (Exception $e) {
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
                            ACL::GRANT_ACTION_PUSH
                        );
                    } else {
                        $this->getAclForUser($user_receiver)->grant_preview_on(
                            $basketElement->getRecord($this->app),
                            $this->getAuthenticatedUser(),
                            ACL::GRANT_ACTION_PUSH
                        );
                    }

                    $this->getDataboxLogger($element->getDatabox())->log(
                        $element,
                        Session_Logger::EVENT_PUSH,
                        $user_receiver->getId(),
                        ''
                    );
                }

                $manager->flush();

                $arguments = [
                    'basket' => $Basket->getId(),
                ];

                // here we send an email to each participant
                //
                // if we don't request the user to auth (=type his login/pwd),
                //  we generate a !!!! 'view' !!!! token to be included as 'LOG' parameter in url
                //
                // - the 'view' token is created to give access to lightbox in "compare" mode, NOT "validation"
                // - the 'view' token has no expiration
                //
                if (!$this->getConf()->get(['registry', 'actions', 'enable-push-authentication'])  || !$request->get('force_authentication') ) {
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


    /** ----------------------------------------------------------------------------------
     * a validation request is made by the current user to many participants
     *
     * this is the same code as "send" request (=simple push), except here we create a validation session,
     *   register participants and data...
     *
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
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
            $validation_description = $request->request->get('message');

            $participants = $request->request->get('participants');

            if (!is_array($participants) || empty($participants)) {
                throw new ControllerException($this->app->trans('No participants specified'));
            }

            if (!is_array($pusher->get_elements()) || empty($pusher->get_elements())) {
                throw new ControllerException($this->app->trans('No elements to validate'));
            }

            // a validation must apply to a basket...
            //
            if ($pusher->is_basket()) {
                $basket = $pusher->get_original_basket();
            }
            else {
                // ...so if we got a list of elements (records), we create a basket for those
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

            // if the basket is already under validation, we work on it
            // else we create a validationSession
            //
            $expireDate = null;
            if (!$basket->getValidation()) {
                // create the validationSession
                $Validation = new ValidationSession();
                $Validation->setInitiator($this->getAuthenticatedUser());
                $Validation->setBasket($basket);

                // add an expiration date if a duration was specified
                $duration = (int)$request->request->get('duration');

                if ($duration > 0) {
                    $expireDate = new DateTime('+' . $duration . ' day' . ($duration > 1 ? 's' : ''));
                    $Validation->setExpires($expireDate);
                }

                $basket->setValidation($Validation);
                $manager->persist($Validation);
            }
            else {
                // go on with existing validationSession
                $Validation = $basket->getValidation();
                $expireDate = $Validation->getExpires();
            }

            // we always add the author of the validation request (current user) to the participants
            //
            $found = false;
            foreach ($participants as $key => $participant) {
                if ($Validation->isInitiator($this->getAuthenticatedUser()) && $participant['usr_id'] == $this->getAuthenticatedUser()->getId()) {
                    // the initiator always can see others
                    $participants[$key]['see_others'] = 1;
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

            // used to check participant to be removed
            $feedbackAction = $request->request->get('feedbackAction');
            $remainingParticipantsUserId = [];
            if ($feedbackAction == 'adduser') {
                $remainingParticipantsUserId = $Validation->getListParticipantsUserId();
            }

            // add participants to the validationSession
            //
            foreach ($participants as $key => $participant) {

                // sanity check
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
                    if ($feedbackAction == 'adduser') {
                        $remainingParticipantsUserId = array_diff($remainingParticipantsUserId, [$participant['usr_id']]);
                    }

                }
                catch (Exception $e) {
                    throw new ControllerException(
                        $this->app->trans('Unknown user %usr_id%', ['%usr_id%' => $participant['usr_id']])
                    );
                }
                // end of sanity check

                // if participant already exists, just update right
                try {
                    $validationParticipant = $Validation->getParticipant($participantUser);
                    $validationParticipant->setCanAgree($participant['agree']);
                    $validationParticipant->setCanSeeOthers($participant['see_others']);
                    $manager->persist($validationParticipant);
                    $manager->flush();

                    continue;
                }
                catch (NotFoundHttpException $e) {
                    // participant not yet exists, create
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
                            ACL::GRANT_ACTION_VALIDATE
                        );
                    }
                    else {
                        $this->getAclForUser($participantUser)->grant_preview_on(
                            $basketElement->getRecord($this->app),
                            $this->getAuthenticatedUser(),
                            ACL::GRANT_ACTION_VALIDATE
                        );
                    }

                    $manager->merge($basketElement);
                    $manager->persist($validationData);

                    $this->getDataboxLogger($basketElement->getRecord($this->app)->getDatabox())->log(
                        $basketElement->getRecord($this->app),
                        Session_Logger::EVENT_VALIDATE,
                        $participantUser->getId(),
                        ''
                    );

                    $validationParticipant->addData($validationData);
                }

                /** @var ValidationParticipant $validationParticipant */
                $validationParticipant = $manager->merge($validationParticipant);

                $manager->flush();

                $arguments = [
                    'basket' => $basket->getId(),
                ];

                // here we send an email to each participant
                //
                // if we don't request the user to auth (=type his login/pwd),
                //  we generate a !!!! 'validate' !!!! token to be included as 'LOG' parameter in url
                //
                // - the 'validate' token has same expiration as validation-session (except for initiator)
                //
                if (!$this->getConf()->get(['registry', 'actions', 'enable-push-authentication']) || !$request->get('force_authentication') ) {
                    if($participantUser->getId() === $this->getAuthenticatedUser()->getId()) {
                        // the initiator of the validation gets a no-expire token (so he can see result after validation expiration)
                        $arguments['LOG'] = $this->getTokenManipulator()->createBasketValidationToken($basket, $participantUser, null)->getValue();
                    }
                    else {
                        // a "normal" participant/user gets a expiring token
                        $arguments['LOG'] = $this->getTokenManipulator()->createBasketValidationToken($basket, $participantUser, $expireDate)->getValue();
                    }
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

            if ($feedbackAction == 'adduser') {
                foreach ($remainingParticipantsUserId as $userIdToRemove) {
                    try {
                        /** @var  User $participantUser */
                        $participantUser = $this->getUserRepository()->find($userIdToRemove);
                    } catch (Exception $e) {
                        throw new ControllerException(
                            $this->app->trans('Unknown user %usr_id%', ['%usr_id%' => $userIdToRemove])
                        );
                    }
                    $validationParticipant = $Validation->getParticipant($participantUser);

                    // if initiator is removed from the user selection,
                    // do not remove it to the participant list, just set can_agree to false for it
                    if ($Validation->isInitiator($participantUser)) {
                        $validationParticipant->setCanAgree(false);
                        $manager->persist($validationParticipant);
                    } else {
                        $Validation->removeParticipant($validationParticipant);
                        $manager->remove($validationParticipant);
                    }
                }
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

    /**
     * @param $usr_id
     * @return JsonResponse
     */
    public function getUserAction($usr_id)
    {
        $data = null;

        $query = $this->createUserQuery();
        $query->on_bases_where_i_am($this->getAclForUser($this->getAuthenticatedUser()), [ACL::CANPUSH]);

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

    /**
     * @param $list_id
     * @return JsonResponse
     */
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

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function addUserAction(Request $request)
    {
        $result = ['success' => false, 'message' => '', 'user'    => null];

        try {
            if (!$this->getAclForUser($this->getAuthenticatedUser())->has_right(ACL::CANADMIN))
                throw new ControllerException($this->app->trans('You are not allowed to add users'));

            if (!$request->request->get('firstname'))
                throw new ControllerException($this->app->trans('First name is required'));

            if (!$request->request->get('lastname'))
                throw new ControllerException($this->app->trans('Last name is required'));

            if (!$request->request->get('email'))
                throw new ControllerException($this->app->trans('Email is required'));

            if (!Swift_Validate::email($request->request->get('email')))
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
            $manager = $this->getEntityManager();

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
                $user->setJob($request->request->get('job'));
            }
            if ($request->request->get('city')) {
                $this->getUserManipulator()->setGeonameId($user, $request->request->get('city'));
            }

            $manager->persist($user);
            $manager->flush();

            $result['message'] = $this->app->trans('User successfully created');
            $result['success'] = true;
            $result['user'] = $this->formatUser($user);
        } catch (Exception $e) {
            $result['message'] = $this->app->trans('Error while creating user');
        }

        return $this->app->json($result);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getAddUserFormAction(Request $request)
    {
        $params = ['callback' => $request->query->get('callback')];

        return $this->render('prod/User/Add.html.twig', $params);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function searchUserAction(Request $request)
    {
        $query = $this->createUserQuery();
        $query->on_bases_where_i_am($this->getAclForUser($this->getAuthenticatedUser()), [ACL::CANPUSH]);
        $query
            ->like(User_Query::LIKE_FIRSTNAME, $request->query->get('query'))
            ->like(User_Query::LIKE_LASTNAME, $request->query->get('query'))
            ->like(User_Query::LIKE_LOGIN, $request->query->get('query'))
            ->like(User_Query::LIKE_EMAIL, $request->query->get('query'))
            ->like(User_Query::LIKE_COMPANY, $request->query->get('query'))
            ->like_match(User_Query::LIKE_MATCH_OR);

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

    /**
     * @param Request $request
     * @param $list_id
     * @return Response
     */
    public function editListAction(Request $request, $list_id)
    {
        $repository = $this->getUserListRepository();
        $list = $repository->findUserListByUserAndId($this->getAuthenticatedUser(), $list_id);

        $query = $this->createUserQuery();
        $query->on_bases_where_i_am($this->getAclForUser($this->getAuthenticatedUser()), [ACL::CANPUSH]);

        if ($request->get('query')) {
            $query
                ->like($request->get('like_field'), $request->get('query'))
                ->like_match(User_Query::LIKE_MATCH_OR);
        }

        if (is_array($request->get('EmailDomain'))) {
            $query->haveEmailDomains($request->get('EmailDomain'));
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

    /**
     * update the expiration date of a validation session
     * also update the expiration of the participants validation tokens
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function updateExpirationAction(Request $request)
    {
        // sanity check
        if (is_null($request->request->get('date'))) {
            throw new Exception('The provided date is null!');
        }

        $manager = $this->getEntityManager();
        $manager->beginTransaction();
        try {
            $basket = $this->getBasketRepository()->findUserBasket($request->request->get('basket_id'), $this->app->getAuthenticatedUser(), true);
            $expirationDate = new DateTime($request->request->get('date') . " 23:59:59");
            $validation = $basket->getValidation();
            if (is_null($validation)) {
                throw new Exception('Unable to find the validation session');
            }

            // update validation tokens expiration
            //
            /** @var ValidationParticipant $participant */
            foreach($validation->getParticipants() as $participant) {
                try {
                    if(!is_null($token = $this->getTokenRepository()->findValidationToken($basket, $participant->getUser()))) {
                        if($participant->getUser()->getId() === $validation->getInitiator()->getId()) {
                            // the initiator keeps a no-expiration token
                            $token->setExpiration(null);    // shoud already be null, but who knows...
                        }
                        else {
                            // the "normal" user token is fixed
                            $token->setExpiration($expirationDate);
                        }
                    }
                }
                catch (Exception $e) {
                    // not unique token ? should not happen.
                    // no-op
                }
            }

            $validation->setExpires($expirationDate);
            $manager->persist($validation);
            $manager->flush();
            $manager->commit();
            $ret = [
                'success' => true,
                'message' => $this->app->trans('Expiration date successfully updated!')
            ];
        }
        catch (Exception $e) {
            $ret = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            $manager->rollback();
        }

        return $this->app->json($ret);
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
     * @param array|record_adapter[] $selection
     * @return ArrayCollection      Users
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

        $feedbackaction = $request->request->get('feedbackaction');
        $participants = [];

        if ($context === 'Feedback' && $feedbackaction === 'adduser' && $push->is_basket() && $push->get_original_basket()->getValidation()) {
            $participants = $push->get_original_basket()->getValidation()->getParticipants();
        } elseif ($context === 'Feedback') {
            // Display the initiator in the participant list window when the first time to create a feedback
            $validationParticipant =  new ValidationParticipant();
            $validationParticipant->setUser($this->getAuthenticatedUser());
            $validationParticipant->setCanSeeOthers(1);
            array_push($participants, $validationParticipant);
        }

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
                'participants'     => $participants,
                'feedbackAction'   => $feedbackaction
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

    /**
     * @return BasketRepository
     */
    private function getBasketRepository()
    {
        return $this->app['repo.baskets'];
    }

    /**
     * @return TokenRepository
     */
    private function getTokenRepository()
    {
        return $this->app['repo.tokens'];
    }

}
