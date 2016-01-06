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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\Exception as ControllerException;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\UsrList;
use Alchemy\Phrasea\Model\Entities\UsrListEntry;
use Alchemy\Phrasea\Model\Entities\UsrListOwner;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\UsrListEntryRepository;
use Alchemy\Phrasea\Model\Repositories\UsrListOwnerRepository;
use Alchemy\Phrasea\Model\Repositories\UsrListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UsrListController extends Controller
{
    use EntityManagerAware;

    public function getAll(Request $request)
    {
        $data = [
            'success' => false,
            'message' => '',
            'result'  => null,
        ];

        try {
            $repository = $this->getUsrListRepository();

            $lists = $repository->findUserLists($this->getAuthenticatedUser());

            $result = [];

            foreach ($lists as $list) {
                $owners = $entries = [];

                foreach ($list->getOwners() as $owner) {
                    $user = $owner->getUser();
                    $owners[] = [
                        'usr_id'       => $user->getId(),
                        'display_name' => $user->getDisplayName(),
                        'position'     => $user->getActivity(),
                        'job'          => $user->getJob(),
                        'company'      => $user->getCompany(),
                        'email'        => $user->getEmail(),
                        'role'         => $owner->getRole()
                    ];
                }

                foreach ($list->getEntries() as $entry) {
                    $user = $entry->getUser();
                    $entries[] = [
                        'usr_id'       => $user->getId(),
                        'display_name' => $user->getDisplayName(),
                        'position'     => $user->getActivity(),
                        'job'          => $user->getJob(),
                        'company'      => $user->getCompany(),
                        'email'        => $user->getEmail(),
                    ];
                }

                $result[] = [
                    'name'    => $list->getName(),
                    'created' => $list->getCreated()->format(DATE_ATOM),
                    'updated' => $list->getUpdated()->format(DATE_ATOM),
                    'owners'  => $owners,
                    'users'   => $entries,
                ];
            }

            $data = [
                'success' => true,
                'message' => '',
                'result'  => $result,
            ];
        } catch (ControllerException $e) {
            $lists = [];
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $lists = [];
        }

        if ($request->getRequestFormat() == 'json') {
            return $this->app->json($data);
        }

        return $this->render('prod/actions/Feedback/lists-all.html.twig', ['lists' => $lists]);
    }

    public function createList(Request $request)
    {
        $list_name = $request->request->get('name');

        $data = [
            'success' => false,
            'message' => $this->app->trans('Unable to create list %name%', ['%name%' => $list_name]),
            'list_id' => null,
        ];

        try {
            if (!$list_name) {
                throw new ControllerException($this->app->trans('List name is required'));
            }

            $List = new UsrList();

            $owner = new UsrListOwner();
            $owner->setRole(UsrListOwner::ROLE_ADMIN);
            $owner->setUser($this->getAuthenticatedUser());
            $owner->setList($List);

            $List->setName($list_name);
            $List->addOwner($owner);

            $manager = $this->getEntityManager();
            $manager->persist($owner);
            $manager->persist($List);
            $manager->flush();

            $data = [
                'success' => true,
                'message' => $this->app->trans('List %name% has been created', ['%name%' => $list_name]),
                'list_id' => $List->getId(),
            ];
        } catch (ControllerException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            // Intentionally left empty
        }

        return $this->app->json($data);
    }

    public function displayList($list_id)
    {
        $repository = $this->getUsrListRepository();

        $list = $repository->findUserListByUserAndId($this->getAuthenticatedUser(), $list_id);

        $entries = new ArrayCollection();
        $owners = new ArrayCollection();

        foreach ($list->getOwners() as $owner) {
            $user = $owner->getUser();
            $owners[] = [
                'usr_id'       => $user->getId(),
                'display_name' => $user->getDisplayName(),
                'position'     => $user->getActivity(),
                'job'          => $user->getJob(),
                'company'      => $user->getCompany(),
                'email'        => $user->getEmail(),
                'role'         => $owner->getRole(),
            ];
        }

        foreach ($list->getEntries() as $entry) {
            $user = $entry->getUser();
            $entries[] = [
                'usr_id'       => $user->getId(),
                'display_name' => $user->getDisplayName(),
                'position'     => $user->getActivity(),
                'job'          => $user->getJob(),
                'company'      => $user->getCompany(),
                'email'        => $user->getEmail(),
            ];
        }

        return $this->app->json([
            'result' => [
                'id'      => $list->getId(),
                'name'    => $list->getName(),
                'created' => $list->getCreated()->format(DATE_ATOM),
                'updated' => $list->getUpdated()->format(DATE_ATOM),
                'owners'  => $owners,
                'users'   => $entries,
            ]
        ]);
    }

    public function updateList(Request $request, $list_id)
    {
        try {
            $list_name = $request->request->get('name');

            if (!$list_name) {
                throw new ControllerException($this->app->trans('List name is required'));
            }

            $repository = $this->getUsrListRepository();

            $user = $this->getAuthenticatedUser();
            $list = $repository->findUserListByUserAndId($user, $list_id);

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException($this->app->trans('You are not authorized to do this'));
            }

            $list->setName($list_name);

            $this->getEntityManager()->flush();

            $data = [
                'success' => true,
                'message' => $this->app->trans('List has been updated'),
            ];
        } catch (ControllerException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'message' => $this->app->trans('Unable to update list'),
            ];

        }

        return $this->app->json($data);
    }

    public function removeList($list_id)
    {
        try {
            $repository = $this->getUsrListRepository();

            $user = $this->getAuthenticatedUser();
            $list = $repository->findUserListByUserAndId($user, $list_id);

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_ADMIN) {
                throw new ControllerException($this->app->trans('You are not authorized to do this'));
            }

            $manager = $this->getEntityManager();
            $manager->remove($list);
            $manager->flush();

            $data = [
                'success' => true,
                'message' => $this->app->trans('List has been deleted'),
            ];
        } catch (ControllerException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'message' => $this->app->trans('Unable to delete list'),
            ];
        }

        return $this->app->json($data);
    }

    public function removeUser($list_id, $usr_id)
    {
        try {
            $repository = $this->getUsrListRepository();

            $user = $this->getAuthenticatedUser();
            $list = $repository->findUserListByUserAndId($user, $list_id);

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException($this->app->trans('You are not authorized to do this'));
            }

            $entry_repository = $this->getUsrListEntryRepository();

            $user_entry = $entry_repository->findEntryByListAndUsrId($list, $usr_id);

            $manager = $this->getEntityManager();
            $manager->remove($user_entry);
            $manager->flush();

            $data = [
                'success' => true,
                'message' => $this->app->trans('Entry removed from list'),
            ];
        } catch (ControllerException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'message' => $this->app->trans('Unable to remove entry from list'),
            ];
        }

        return $this->app->json($data);
    }

    public function addUsers(Request $request, $list_id)
    {
        try {
            if (!is_array($request->request->get('usr_ids'))) {
                throw new ControllerException('Invalid or missing parameter usr_ids');
            }

            $repository = $this->getUsrListRepository();

            $user = $this->getAuthenticatedUser();
            $list = $repository->findUserListByUserAndId($user, $list_id);

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException($this->app->trans('You are not authorized to do this'));
            }

            $inserted_usr_ids = [];

            $manager = $this->getEntityManager();
            $userIds = $request->request->get('usr_ids');
            if (! is_array($userIds)) {
                throw new \InvalidArgumentException('A usr_ids key should be provider');
            }
            $userIds = array_unique($userIds);
            /** @var User[] $users */
            $users = $this->getUserRepository()->findBy(['id' => $userIds]);
            if (count($userIds) !== count($users)) {
                throw new NotFoundHttpException('At least one user was not found');
            }

            foreach ($users as $user_entry) {
                if ($list->has($user_entry)) {
                    continue;
                }

                $entry = new UsrListEntry();
                $entry->setUser($user_entry);
                $entry->setList($list);

                $list->addEntrie($entry);

                $manager->persist($entry);

                $inserted_usr_ids[] = $user_entry->getId();
            }

            $manager->flush();

            if (count($inserted_usr_ids) > 1) {
                $data = [
                    'success' => true,
                    'message' => $this->app->trans('%quantity% Users added to list', [
                        '%quantity%' => count($inserted_usr_ids),
                    ]),
                    'result'  => $inserted_usr_ids,
                ];
            } else {
                $data = [
                    'success' => true,
                    'message' => $this->app->trans('%quantity% User added to list', [
                        '%quantity%' => count($inserted_usr_ids),
                    ]),
                    'result'  => $inserted_usr_ids,
                ];
            }
        } catch (ControllerException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'message' => $this->app->trans('Unable to add usr to list'),
            ];
        }

        return $this->app->json($data);
    }

    public function displayShares($list_id)
    {
        $list = null;

        try {
            $repository = $this->getUsrListRepository();

            $user = $this->getAuthenticatedUser();
            $list = $repository->findUserListByUserAndId($user, $list_id);

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_ADMIN) {
                $list = null;
                throw new \Exception($this->app->trans('You are not authorized to do this'));
            }
        } catch (\Exception $e) {

        }

        return $this->render('prod/actions/Feedback/List-Share.html.twig', ['list' => $list]);
    }

    public function shareWithUser(Request $request, $list_id, $usr_id)
    {
        $availableRoles = [
            UsrListOwner::ROLE_USER,
            UsrListOwner::ROLE_EDITOR,
            UsrListOwner::ROLE_ADMIN,
        ];

        if (!$request->request->get('role'))
            throw new BadRequestHttpException('Missing role parameter');
        elseif (!in_array($request->request->get('role'), $availableRoles))
            throw new BadRequestHttpException('Role is invalid');

        try {
            $repository = $this->getUsrListRepository();

            $user = $this->getAuthenticatedUser();
            $list = $repository->findUserListByUserAndId($user, $list_id);

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException($this->app->trans('You are not authorized to do this'));
            }

            /** @var User $new_owner */
            $new_owner = $this->getUserRepository()->find($usr_id);

            if ($list->hasAccess($new_owner)) {
                if ($new_owner->getId() == $user->getId()) {
                    throw new ControllerException('You can not downgrade your Admin right');
                }

                $owner = $list->getOwner($new_owner);
            } else {
                $owner = new UsrListOwner();
                $owner->setList($list);
                $owner->setUser($new_owner);

                $list->addOwner($owner);

                $this->getEntityManager()->persist($owner);
            }

            $role = $request->request->get('role');

            $owner->setRole($role);

            $this->getEntityManager()->flush();

            $data = [
                'success' => true
                , 'message' => $this->app->trans('List shared to user')
            ];
        } catch (ControllerException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'message' => $this->app->trans('Unable to share the list with the usr'),
            ];
        }

        return $this->app->json($data);
    }

    public function unshareWithUser($list_id, $usr_id)
    {
        try {
            $repository = $this->getUsrListRepository();

            $user = $this->getAuthenticatedUser();
            $list = $repository->findUserListByUserAndId($user, $list_id);

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_ADMIN) {
                throw new \Exception($this->app->trans('You are not authorized to do this'));
            }

            $owners_repository = $this->getUsrListOwnerRepository();

            $owner = $owners_repository->findByListAndUsrId($list, $usr_id);

            $manager = $this->getEntityManager();
            $manager->remove($owner);
            $manager->flush();

            $data = [
                'success' => true,
                'message' => $this->app->trans('Owner removed from list'),
            ];
        } catch (ControllerException $e) {
            $data = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'message' => $this->app->trans('Unable to remove usr from list'),
            ];
        }

        return $this->app->json($data);
    }

    /**
     * @return UsrListRepository
     */
    private function getUsrListRepository()
    {
        return $this->app['repo.usr-lists'];
    }

    /**
     * @return UsrListEntryRepository
     */
    private function getUsrListEntryRepository()
    {
        return $this->app['repo.usr-list-entries'];
    }

    /**
     * @return UserRepository
     */
    private function getUserRepository()
    {
        return $this->app['repo.users'];
    }

    /**
     * @return UsrListOwnerRepository
     */
    private function getUsrListOwnerRepository()
    {
        return $this->app['repo.usr-list-owners'];
    }
}
