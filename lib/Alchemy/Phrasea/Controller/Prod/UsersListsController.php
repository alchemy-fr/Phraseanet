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
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Alchemy\Phrasea\Model\Entities\BasketParticipant;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrList;
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
use Swift_Validate;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use User_Query;

class UsersListsController extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;
    use EntityManagerAware;
    use UserQueryAware;

    /**
     * @param Request $request
     * @param         $context
     * @return string
     */
    public function postFormAction(Request $request)
    {
        $repository = $this->getUserListRepository();

        return $this->render(
            'prod/actions/Push.html.twig',
            [
                'lists'            => $repository->findUserLists($this->getAuthenticatedUser()),
            ]
        );
    }

    /**
     * @return UsrListRepository
     */
    private function getUserListRepository()
    {
        return $this->app['repo.usr-lists'];
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
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->app['repo.users'];
    }

    /**
     * @return Generator
     */
    private function getRandomGenerator()
    {
        return $this->app['random.medium'];
    }

    /**
     * @return UserManipulator
     */
    private function getUserManipulator()
    {
        return $this->app['manipulator.user'];
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

            if (!$basket->isVoteBasket()) {
                throw new Exception('Unable to find the validation session');
            }

            // update validation tokens expiration
            //
            /** @var BasketParticipant $participant */
            foreach($basket->getParticipants() as $participant) {
                try {
                    if(!is_null($token = $this->getTokenRepository()->findValidationToken($basket, $participant->getUser()))) {
                        if($participant->getUser()->getId() === $basket->getVoteInitiator()->getId()) {
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

            $basket->setVoteExpires($expirationDate);
            $manager->persist($basket);
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

    /**
     * from a list of records, return "users" from field(s) declared as vocabularyType/user
     *
     * this list of users will be displayed as "RecommendedUsers" for push
     * !!!!!!!!!!!!!!! todo : also for share baskets ? !!!!!!!!!!!!
     * !!!!! useless (?) in 4.1 since editing a vocab field does nothing special :
     *       the field value has no vocab/id value
     *
     *
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

}
