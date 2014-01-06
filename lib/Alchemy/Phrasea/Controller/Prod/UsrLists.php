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

use Alchemy\Phrasea\Model\Entities\UsrList;
use Alchemy\Phrasea\Model\Entities\UsrListEntry;
use Alchemy\Phrasea\Model\Entities\UsrListOwner;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Controller\Exception as ControllerException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UsrLists implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.usr-lists'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAuthentication();
        });

        $controllers->get('/all/', 'controller.prod.usr-lists:getAll')
            ->bind('prod_lists_all');

        $controllers->post('/list/', 'controller.prod.usr-lists:createList')
            ->bind('prod_lists_list');

        $controllers->get('/list/{list_id}/', 'controller.prod.usr-lists:displayList')
            ->assert('list_id', '\d+');

        $controllers->post('/list/{list_id}/update/', 'controller.prod.usr-lists:updateList')
            ->bind('prod_lists_list_update')
            ->assert('list_id', '\d+');

        $controllers->post('/list/{list_id}/delete/', 'controller.prod.usr-lists:removeList')
            ->assert('list_id', '\d+');

        $controllers->post('/list/{list_id}/remove/{usr_id}/', 'controller.prod.usr-lists:removeUser')
            ->assert('list_id', '\d+')
            ->assert('usr_id', '\d+');

        $controllers->post('/list/{list_id}/add/', 'controller.prod.usr-lists:addUsers')
            ->assert('list_id', '\d+');

        $controllers->get('/list/{list_id}/share/', 'controller.prod.usr-lists:displayShares')
            ->assert('list_id', '\d+')
            ->bind('prod_lists_list_share');

        $controllers->post('/list/{list_id}/share/{usr_id}/', 'controller.prod.usr-lists:shareWithUser')
            ->assert('list_id', '\d+')
            ->assert('usr_id', '\d+');

        $controllers->post('/list/{list_id}/unshare/{usr_id}/', 'controller.prod.usr-lists:unshareWithUser')
            ->assert('list_id', '\d+')
            ->assert('usr_id', '\d+');

        return $controllers;
    }

    public function getAll(Application $app, Request $request)
    {
        $datas = [
            'success' => false
            , 'message' => ''
            , 'result'  => null
        ];

        $lists = new ArrayCollection();

        try {
            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');

            $lists = $repository->findUserLists($app['authentication']->getUser());

            $result = [];

            foreach ($lists as $list) {
                $owners = $entries = [];

                foreach ($list->getOwners() as $owner) {
                    $owners[] = [
                        'usr_id'       => $owner->getUser($app)->get_id(),
                        'display_name' => $owner->getUser($app)->get_display_name(),
                        'position'     => $owner->getUser($app)->get_position(),
                        'job'          => $owner->getUser($app)->get_job(),
                        'company'      => $owner->getUser($app)->get_company(),
                        'email'        => $owner->getUser($app)->get_email(),
                        'role'         => $owner->getRole()
                    ];
                }

                foreach ($list->getEntries() as $entry) {
                    $entries[] = [
                        'usr_id'       => $owner->getUser($app)->get_id(),
                        'display_name' => $owner->getUser($app)->get_display_name(),
                        'position'     => $owner->getUser($app)->get_position(),
                        'job'          => $owner->getUser($app)->get_job(),
                        'company'      => $owner->getUser($app)->get_company(),
                        'email'        => $owner->getUser($app)->get_email(),
                    ];
                }

                /* @var $list UsrList */
                $result[] = [
                    'name'    => $list->getName(),
                    'created' => $list->getCreated()->format(DATE_ATOM),
                    'updated' => $list->getUpdated()->format(DATE_ATOM),
                    'owners'  => $owners,
                    'users'   => $entries
                ];
            }

            $datas = [
                'success' => true
                , 'message' => ''
                , 'result'  => $result
            ];
        } catch (ControllerException $e) {
            $datas = [
                'success' => false
                , 'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {

        }

        if ($request->getRequestFormat() == 'json') {
            return $app->json($datas);
        }

        return $app['twig']->render('prod/actions/Feedback/lists-all.html.twig', ['lists' => $lists]);
    }

    public function createList(Application $app)
    {
        $request = $app['request'];

        $list_name = $request->request->get('name');

        $datas = [
            'success' => false
            , 'message' => $app->trans('Unable to create list %name%', ['%name%' => $list_name])
            , 'list_id' => null
        ];

        try {
            if (!$list_name) {
                throw new ControllerException($app->trans('List name is required'));
            }

            $List = new UsrList();

            $Owner = new UsrListOwner();
            $Owner->setRole(UsrListOwner::ROLE_ADMIN);
            $Owner->setUser($app['authentication']->getUser());
            $Owner->setList($List);

            $List->setName($list_name);
            $List->addOwner($Owner);

            $app['EM']->persist($Owner);
            $app['EM']->persist($List);
            $app['EM']->flush();

            $datas = [
                'success' => true
                , 'message' => $app->trans('List %name% has been created', ['%name%' => $list_name])
                , 'list_id' => $List->getId()
            ];
        } catch (ControllerException $e) {
            $datas = [
                'success' => false
                , 'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {

        }

        return $app->json($datas);
    }

    public function displayList(Application $app, Request $request, $list_id)
    {
        $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');

        $list = $repository->findUserListByUserAndId($app, $app['authentication']->getUser(), $list_id);

        $entries = new ArrayCollection();
        $owners = new ArrayCollection();

        foreach ($list->getOwners() as $owner) {
            $owners[] = [
                'usr_id'       => $owner->getUser($app)->get_id(),
                'display_name' => $owner->getUser($app)->get_display_name(),
                'position'     => $owner->getUser($app)->get_position(),
                'job'          => $owner->getUser($app)->get_job(),
                'company'      => $owner->getUser($app)->get_company(),
                'email'        => $owner->getUser($app)->get_email(),
                'role'         => $owner->getRole($app)
            ];
        }

        foreach ($list->getEntries() as $entry) {
            $entries[] = [
                'usr_id'       => $entry->getUser($app)->get_id(),
                'display_name' => $entry->getUser($app)->get_display_name(),
                'position'     => $entry->getUser($app)->get_position(),
                'job'          => $entry->getUser($app)->get_job(),
                'company'      => $entry->getUser($app)->get_company(),
                'email'        => $entry->getUser($app)->get_email(),
            ];
        }

        return $app->json([
            'result' => [
                'id'      => $list->getId(),
                'name'    => $list->getName(),
                'created' => $list->getCreated()->format(DATE_ATOM),
                'updated' => $list->getUpdated()->format(DATE_ATOM),
                'owners'  => $owners,
                'users'   => $entries
            ]
        ]);
    }

    public function updateList(Application $app, $list_id)
    {
        $request = $app['request'];

        $datas = [
            'success' => false
            , 'message' => $app->trans('Unable to update list')
        ];

        try {
            $list_name = $request->request->get('name');

            if (!$list_name) {
                throw new ControllerException($app->trans('List name is required'));
            }

            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($app, $app['authentication']->getUser(), $list_id);

            if ($list->getOwner($app['authentication']->getUser(), $app)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException($app->trans('You are not authorized to do this'));
            }

            $list->setName($list_name);

            $app['EM']->flush();

            $datas = [
                'success' => true
                , 'message' => $app->trans('List has been updated')
            ];
        } catch (ControllerException $e) {
            $datas = [
                'success' => false
                , 'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {

        }

        return $app->json($datas);
    }

    public function removeList(Application $app, $list_id)
    {
        try {
            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($app, $app['authentication']->getUser(), $list_id);

            if ($list->getOwner($app['authentication']->getUser(), $app)->getRole() < UsrListOwner::ROLE_ADMIN) {
                throw new ControllerException($app->trans('You are not authorized to do this'));
            }

            $app['EM']->remove($list);
            $app['EM']->flush();

            $datas = [
                'success' => true
                , 'message' => $app->trans('List has been deleted')
            ];
        } catch (ControllerException $e) {
            $datas = [
                'success' => false
                , 'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {

            $datas = [
                'success' => false
                , 'message' => $app->trans('Unable to delete list')
            ];
        }

        return $app->json($datas);
    }

    public function removeUser(Application $app, $list_id, $usr_id)
    {
        try {
            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($app, $app['authentication']->getUser(), $list_id);
            /* @var $list UsrList */

            if ($list->getOwner($app['authentication']->getUser(), $app)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException($app->trans('You are not authorized to do this'));
            }

            $entry_repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrListEntry');

            $user_entry = $entry_repository->findEntryByListAndUsrId($list, $usr_id);

            $app['EM']->remove($user_entry);
            $app['EM']->flush();

            $datas = [
                'success' => true
                , 'message' => $app->trans('Entry removed from list')
            ];
        } catch (ControllerException $e) {
            $datas = [
                'success' => false
                , 'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $datas = [
                'success' => false,
                'message' => $app->trans('Unable to remove entry from list'),
            ];
        }

        return $app->json($datas);
    }

    public function addUsers(Application $app, Request $request, $list_id)
    {
        try {
            if (!is_array($request->request->get('usr_ids'))) {
                throw new ControllerException('Invalid or missing parameter usr_ids');
            }

            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($app, $app['authentication']->getUser(), $list_id);
            /* @var $list UsrList */

            if ($list->getOwner($app['authentication']->getUser(), $app)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException($app->trans('You are not authorized to do this'));
            }

            $inserted_usr_ids = [];

            foreach ($request->request->get('usr_ids') as $usr_id) {
                $user_entry = \User_Adapter::getInstance($usr_id, $app);

                if ($list->has($user_entry, $app))
                    continue;

                $entry = new UsrListEntry();
                $entry->setUser($user_entry);
                $entry->setList($list);

                $list->addEntrie($entry);

                $app['EM']->persist($entry);

                $inserted_usr_ids[] = $user_entry->get_id();
            }

            $app['EM']->flush();

            if (count($inserted_usr_ids) > 1) {
                $datas = [
                    'success' => true
                    , 'message' => $app->trans('%quantity% Users added to list', ['%quantity%' => count($inserted_usr_ids)])
                    , 'result'  => $inserted_usr_ids
                ];
            } else {
                $datas = [
                    'success' => true
                    , 'message' => $app->trans('%quantity% User added to list', ['%quantity%' => count($inserted_usr_ids)])
                    , 'result'  => $inserted_usr_ids
                ];
            }
        } catch (ControllerException $e) {
            $datas = [
                'success' => false
                , 'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {

            $datas = [
                'success' => false
                , 'message' => $app->trans('Unable to add usr to list')
            ];
        }

        return $app->json($datas);
    }

    public function displayShares(Application $app, Request $request, $list_id)
    {
        $list = null;

        try {
            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($app, $app['authentication']->getUser(), $list_id);
            /* @var $list UsrList */

            if ($list->getOwner($app['authentication']->getUser(), $app)->getRole() < UsrListOwner::ROLE_ADMIN) {
                $list = null;
                throw new \Exception($app->trans('You are not authorized to do this'));
            }
        } catch (\Exception $e) {

        }

        return $app['twig']->render('prod/actions/Feedback/List-Share.html.twig', ['list' => $list]);
    }

    public function shareWithUser(Application $app, $list_id, $usr_id)
    {
        $availableRoles = [
            UsrListOwner::ROLE_USER,
            UsrListOwner::ROLE_EDITOR,
            UsrListOwner::ROLE_ADMIN,
        ];

        if (!$app['request']->request->get('role'))
            throw new BadRequestHttpException('Missing role parameter');
        elseif (!in_array($app['request']->request->get('role'), $availableRoles))
            throw new BadRequestHttpException('Role is invalid');

        try {
            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($app, $app['authentication']->getUser(), $list_id);
            /* @var $list UsrList */

            if ($list->getOwner($app['authentication']->getUser(), $app)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException($app->trans('You are not authorized to do this'));
            }

            $new_owner = \User_Adapter::getInstance($usr_id, $app);

            if ($list->hasAccess($new_owner, $app)) {
                if ($new_owner->get_id() == $app['authentication']->getUser()->get_id()) {
                    throw new ControllerException('You can not downgrade your Admin right');
                }

                $owner = $list->getOwner($new_owner, $app);
            } else {
                $owner = new UsrListOwner();
                $owner->setList($list);
                $owner->setUser($new_owner);

                $list->addOwner($owner);

                $app['EM']->persist($owner);
            }

            $role = $app['request']->request->get('role');

            $owner->setRole($role);

            $app['EM']->flush();

            $datas = [
                'success' => true
                , 'message' => $app->trans('List shared to user')
            ];
        } catch (ControllerException $e) {
            $datas = [
                'success' => false
                , 'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {

            $datas = [
                'success' => false
                , 'message' => $app->trans('Unable to share the list with the usr')
            ];
        }

        return $app->json($datas);
    }

    public function unshareWithUser(Application $app, $list_id, $usr_id)
    {
        try {
            $repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($app, $app['authentication']->getUser(), $list_id);
            /* @var $list UsrList */

            if ($list->getOwner($app['authentication']->getUser(), $app)->getRole() < UsrListOwner::ROLE_ADMIN) {
                throw new \Exception($app->trans('You are not authorized to do this'));
            }

            $owners_repository = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\UsrListOwner');

            $owner = $owners_repository->findByListAndUsrId($list, $usr_id);

            $app['EM']->remove($owner);
            $app['EM']->flush();

            $datas = [
                'success' => true
                , 'message' => $app->trans('Owner removed from list')
            ];
        } catch (ControllerException $e) {
            $datas = [
                'success' => false
                , 'message' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            $datas = [
                'success' => false
                , 'message' => $app->trans('Unable to remove usr from list')
            ];
        }

        return $app->json($datas);
    }
}
