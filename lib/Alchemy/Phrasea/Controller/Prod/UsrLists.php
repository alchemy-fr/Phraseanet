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

use Entities\UsrList;
use Entities\UsrListEntry;
use Entities\UsrListOwner;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Controller\Exception as ControllerException;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UsrLists implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /**
         * Get all lists
         */
        $controllers->get('/all/', $this->call('getAll'));

        /**
         * Creates a list
         */
        $controllers->post('/list/', $this->call('createList'));

        /**
         * Gets a list
         */
        $controllers->get('/list/{list_id}/', $this->call('displayList'))
            ->assert('list_id', '\d+');

        /**
         * Update a list
         */
        $controllers->post('/list/{list_id}/update/', $this->call('updateList'))
            ->assert('list_id', '\d+');

        /**
         * Delete a list
         */
        $controllers->post('/list/{list_id}/delete/', $this->call('removeList'))
            ->assert('list_id', '\d+');

        /**
         * Remove a usr_id from a list
         */
        $controllers->post('/list/{list_id}/remove/{usr_id}/', $this->call('removeUser'))
            ->assert('list_id', '\d+')
            ->assert('usr_id', '\d+');

        /**
         * Adds a usr_id to a list
         */
        $controllers->post('/list/{list_id}/add/', $this->call('addUsers'))
            ->assert('list_id', '\d+');

        $controllers->get('/list/{list_id}/share/', $this->call('displayShares'))
            ->assert('list_id', '\d+');

        /**
         * Share a list to a user with an optionnal role
         */
        $controllers->post('/list/{list_id}/share/{usr_id}/', $this->call('shareWithUser'))
            ->assert('list_id', '\d+')
            ->assert('usr_id', '\d+');
        /**
         * UnShare a list to a user
         */
        $controllers->post('/list/{list_id}/unshare/{usr_id}/', $this->call('unshareWithUser'))
            ->assert('list_id', '\d+')
            ->assert('usr_id', '\d+');

        return $controllers;
    }

    public function getAll(Application $app, Request $request)
    {
        $datas = array(
            'success' => false
            , 'message' => ''
            , 'result'  => null
        );

        $lists = new ArrayCollection();

        try {
            $em = $app['phraseanet.core']->getEntityManager();

            $repository = $em->getRepository('\Entities\UsrList');

            $lists = $repository->findUserLists($app['phraseanet.core']->getAuthenticatedUser());

            $result = array();

            foreach ($lists as $list) {
                $owners = $entries = array();

                foreach ($list->getOwners() as $owner) {
                    $owners[] = array(
                        'usr_id'       => $owner->getUser()->get_id(),
                        'display_name' => $owner->getUser()->get_display_name(),
                        'position'     => $owner->getUser()->get_position(),
                        'job'          => $owner->getUser()->get_job(),
                        'company'      => $owner->getUser()->get_company(),
                        'email'        => $owner->getUser()->get_email(),
                        'role'         => $owner->getRole()
                    );
                }

                foreach ($list->getEntries() as $entry) {
                    $entries[] = array(
                        'usr_id'       => $owner->getUser()->get_id(),
                        'display_name' => $owner->getUser()->get_display_name(),
                        'position'     => $owner->getUser()->get_position(),
                        'job'          => $owner->getUser()->get_job(),
                        'company'      => $owner->getUser()->get_company(),
                        'email'        => $owner->getUser()->get_email(),
                    );
                }

                /* @var $list \Entities\UsrList */
                $result[] = array(
                    'name'    => $list->getName(),
                    'created' => $list->getCreated()->format(DATE_ATOM),
                    'updated' => $list->getUpdated()->format(DATE_ATOM),
                    'owners'  => $owners,
                    'users'   => $entries
                );
            }

            $datas = array(
                'success' => true
                , 'message' => ''
                , 'result'  => $result
            );
        } catch (ControllerException $e) {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );
        } catch (\Exception $e) {

        }

        if ($request->getRequestFormat() == 'json') {
            return $app->json($datas);
        }

        return $app['twig']->render('prod/actions/Feedback/lists-all.html.twig', array('lists' => $lists));
    }

    public function createList(Application $app)
    {
        $request = $app['request'];

        $list_name = $request->request->get('name');

        $datas = array(
            'success' => false
            , 'message' => sprintf(_('Unable to create list %s'), $list_name)
            , 'list_id' => null
        );

        try {
            if ( ! $list_name) {
                throw new ControllerException(_('List name is required'));
            }

            $em = $app['phraseanet.core']->getEntityManager();

            $List = new UsrList();

            $Owner = new UsrListOwner();
            $Owner->setRole(UsrListOwner::ROLE_ADMIN);
            $Owner->setUser($app['phraseanet.core']->getAuthenticatedUser());
            $Owner->setList($List);

            $List->setName($list_name);
            $List->addUsrListOwner($Owner);

            $em->persist($Owner);
            $em->persist($List);
            $em->flush();

            $datas = array(
                'success' => true
                , 'message' => sprintf(_('List %s has been created'), $list_name)
                , 'list_id' => $List->getId()
            );
        } catch (ControllerException $e) {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );
        } catch (\Exception $e) {

        }

        return $app->json($datas);
    }

    public function displayList(Application $app, Request $request, $list_id)
    {
        $user = $app['phraseanet.core']->getAuthenticatedUser();
        $em = $app['phraseanet.core']->getEntityManager();

        $repository = $em->getRepository('\Entities\UsrList');

        $list = $repository->findUserListByUserAndId($user, $list_id);

        $entries = new ArrayCollection();
        $owners = new ArrayCollection();

        foreach ($list->getOwners() as $owner) {
            $owners[] = array(
                'usr_id'       => $owner->getUser()->get_id(),
                'display_name' => $owner->getUser()->get_display_name(),
                'position'     => $owner->getUser()->get_position(),
                'job'          => $owner->getUser()->get_job(),
                'company'      => $owner->getUser()->get_company(),
                'email'        => $owner->getUser()->get_email(),
                'role'         => $owner->getRole()
            );
        }

        foreach ($list->getEntries() as $entry) {
            $entries[] = array(
                'usr_id'       => $entry->getUser()->get_id(),
                'display_name' => $entry->getUser()->get_display_name(),
                'position'     => $entry->getUser()->get_position(),
                'job'          => $entry->getUser()->get_job(),
                'company'      => $entry->getUser()->get_company(),
                'email'        => $entry->getUser()->get_email(),
            );
        }

        return $app->json(array(
                'result' => array(
                    'id'      => $list->getId(),
                    'name'    => $list->getName(),
                    'created' => $list->getCreated()->format(DATE_ATOM),
                    'updated' => $list->getUpdated()->format(DATE_ATOM),
                    'owners'  => $owners,
                    'users'   => $entries
                )
            ));
    }

    public function updateList(Application $app, $list_id)
    {
        $request = $app['request'];

        $datas = array(
            'success' => false
            , 'message' => _('Unable to update list')
        );

        try {
            $list_name = $request->request->get('name');

            if ( ! $list_name) {
                throw new ControllerException(_('List name is required'));
            }

            $user = $app['phraseanet.core']->getAuthenticatedUser();
            $em = $app['phraseanet.core']->getEntityManager();

            $repository = $em->getRepository('\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($user, $list_id);

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException(_('You are not authorized to do this'));
            }

            $list->setName($list_name);

            $em->flush();

            $datas = array(
                'success' => true
                , 'message' => _('List has been updated')
            );
        } catch (ControllerException $e) {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );
        } catch (\Exception $e) {

        }

        return $app->json($datas);
    }

    public function removeList(Application $app, $list_id)
    {
        $em = $app['phraseanet.core']->getEntityManager();

        try {
            $repository = $em->getRepository('\Entities\UsrList');

            $user = $app['phraseanet.core']->getAuthenticatedUser();

            $list = $repository->findUserListByUserAndId($user, $list_id);

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_ADMIN) {
                throw new ControllerException(_('You are not authorized to do this'));
            }

            $em->remove($list);
            $em->flush();

            $datas = array(
                'success' => true
                , 'message' => sprintf(_('List has been deleted'))
            );
        } catch (ControllerException $e) {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );
        } catch (\Exception $e) {

            $datas = array(
                'success' => false
                , 'message' => sprintf(_('Unable to delete list'))
            );
        }

        return $app->json($datas);
    }

    public function removeUser(Application $app, $list_id, $usr_id)
    {
        $em = $app['phraseanet.core']->getEntityManager();

        try {
            $repository = $em->getRepository('\Entities\UsrList');

            $user = $app['phraseanet.core']->getAuthenticatedUser();

            $list = $repository->findUserListByUserAndId($user, $list_id);
            /* @var $list \Entities\UsrList */

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException(_('You are not authorized to do this'));
            }

            $entry_repository = $em->getRepository('\Entities\UsrListEntry');

            $user_entry = $entry_repository->findEntryByListAndUsrId($list, $usr_id);

            $em->remove($user_entry);
            $em->flush();

            $datas = array(
                'success' => true
                , 'message' => _('Entry removed from list')
            );
        } catch (ControllerException $e) {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );
        } catch (\Exception $e) {

            $datas = array(
                'success' => false
                , 'message' => _('Unable to remove entry from list ' . $e->getMessage())
            );
        }

        return $app->json($datas);
    }

    public function addUsers(Application $app, Request $request, $list_id)
    {
        $em = $app['phraseanet.core']->getEntityManager();
        $user = $app['phraseanet.core']->getAuthenticatedUser();

        try {
            if ( ! is_array($request->request->get('usr_ids'))) {
                throw new ControllerException('Invalid or missing parameter usr_ids');
            }

            $repository = $em->getRepository('\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($user, $list_id);
            /* @var $list \Entities\UsrList */

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException(_('You are not authorized to do this'));
            }

            $inserted_usr_ids = array();

            foreach ($request->request->get('usr_ids') as $usr_id) {
                $user_entry = \User_Adapter::getInstance($usr_id, $app['phraseanet.appbox']);

                if ($list->has($user_entry))
                    continue;

                $entry = new UsrListEntry();
                $entry->setUser($user_entry);
                $entry->setList($list);

                $list->addUsrListEntry($entry);

                $em->persist($entry);

                $inserted_usr_ids[] = $user_entry->get_id();
            }

            $em->flush();

            if (count($inserted_usr_ids) > 1) {
                $datas = array(
                    'success' => true
                    , 'message' => sprintf(_('%d Users added to list'), count($inserted_usr_ids))
                    , 'result'  => $inserted_usr_ids
                );
            } else {
                $datas = array(
                    'success' => true
                    , 'message' => sprintf(_('%d User added to list'), count($inserted_usr_ids))
                    , 'result'  => $inserted_usr_ids
                );
            }
        } catch (ControllerException $e) {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );
        } catch (\Exception $e) {

            $datas = array(
                'success' => false
                , 'message' => _('Unable to add usr to list')
            );
        }

        return $app->json($datas);
    }

    public function displayShares(Application $app, Request $request, $list_id)
    {
        $em = $app['phraseanet.core']->getEntityManager();
        $user = $app['phraseanet.core']->getAuthenticatedUser();

        $list = null;

        try {
            $repository = $em->getRepository('\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($user, $list_id);
            /* @var $list \Entities\UsrList */

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_ADMIN) {
                $list = null;
                throw new \Exception(_('You are not authorized to do this'));
            }
        } catch (\Exception $e) {

        }

        return $app['twig']->render('prod/actions/Feedback/List-Share.html.twig', array('list' => $list));
    }

    public function shareWithUser(Application $app, $list_id, $usr_id)
    {
        $em = $app['phraseanet.core']->getEntityManager();
        $user = $app['phraseanet.core']->getAuthenticatedUser();

        $availableRoles = array(
            UsrListOwner::ROLE_USER,
            UsrListOwner::ROLE_EDITOR,
            UsrListOwner::ROLE_ADMIN,
        );

        if ( ! $app['request']->request->get('role'))
            throw new \Exception_BadRequest('Missing role parameter');
        elseif ( ! in_array($app['request']->request->get('role'), $availableRoles))
            throw new \Exception_BadRequest('Role is invalid');

        try {
            $repository = $em->getRepository('\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($user, $list_id);
            /* @var $list \Entities\UsrList */

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_EDITOR) {
                throw new ControllerException(_('You are not authorized to do this'));
            }

            $new_owner = \User_Adapter::getInstance($usr_id, $app['phraseanet.appbox']);

            if ($list->hasAccess($new_owner)) {
                if ($new_owner->get_id() == $user->get_id()) {
                    throw new ControllerException('You can not downgrade your Admin right');
                }

                $owner = $list->getOwner($new_owner);
            } else {
                $owner = new UsrListOwner();
                $owner->setList($list);
                $owner->setUser($new_owner);

                $list->addUsrListOwner($owner);

                $em->persist($owner);
            }

            $role = $app['request']->request->get('role');

            $owner->setRole($role);

            $em->flush();

            $datas = array(
                'success' => true
                , 'message' => _('List shared to user')
            );
        } catch (ControllerException $e) {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );
        } catch (\Exception $e) {

            $datas = array(
                'success' => false
                , 'message' => _('Unable to share the list with the usr')
            );
        }

        return $app->json($datas);
    }

    public function unshareWithUser(Application $app, $list_id, $usr_id)
    {
        $em = $app['phraseanet.core']->getEntityManager();
        $user = $app['phraseanet.core']->getAuthenticatedUser();

        try {
            $repository = $em->getRepository('\Entities\UsrList');

            $list = $repository->findUserListByUserAndId($user, $list_id);
            /* @var $list \Entities\UsrList */

            if ($list->getOwner($user)->getRole() < UsrListOwner::ROLE_ADMIN) {
                throw new \Exception(_('You are not authorized to do this'));
            }

            $owners_repository = $em->getRepository('\Entities\UsrListOwner');

            $owner = $owners_repository->findByListAndUsrId($list, $usr_id);

            $em->remove($owner);
            $em->flush();

            $datas = array(
                'success' => true
                , 'message' => _('Owner removed from list')
            );
        } catch (ControllerException $e) {
            $datas = array(
                'success' => false
                , 'message' => $e->getMessage()
            );
        } catch (\Exception $e) {
            $datas = array(
                'success' => false
                , 'message' => _('Unable to remove usr from list')
            );
        }

        return $app->json($datas);
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
