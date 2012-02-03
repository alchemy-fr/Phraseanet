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
    Alchemy\Phrasea\Out\Module\PDF as PDFExport,
    Alchemy\Phrasea\Controller\Exception as ControllerException;
use Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\RedirectResponse;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UsrLists implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    /**
     * Get all lists
     */
    $controllers->get('/all/', function(Application $app, Request $request)
      {

        $datas = array(
          'success' => false
          , 'message' => ''
          , 'result'  => null
        );

        $lists = new \Doctrine\Common\Collections\ArrayCollection();

        try
        {
          $em = $app['Core']->getEntityManager();

          $repository = $em->getRepository('\Entities\UsrList');

          $lists = $repository->findUserLists($app['Core']->getAuthenticatedUser());

          $result = array();

          foreach ($lists as $list)
          {
            $owners  = $entries = array();

            foreach ($list->getOwners() as $owner)
            {
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

            foreach ($list->getEntries() as $entry)
            {
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
        }
        catch (ControllerException $e)
        {
          $datas = array(
            'success' => false
            , 'message' => $e->getMessage()
          );
        }
        catch (\Exception $e)
        {

        }

        if ($request->getRequestFormat() == 'json')
        {
          $Json = $app['Core']['Serializer']->serialize($datas, 'json');

          return new Response($Json, 200, array('Content-Type' => 'application/json'));
        }
        else
        {

          return new Response($app['Core']->getTwig()->render('prod/actions/Feedback/lists-all.html.twig', array('lists' => $lists)));
        }
      }
    );

    /**
     * Creates a list
     */
    $controllers->post('/list/', function(Application $app)
      {
        $request = $app['request'];

        $list_name = $request->get('name');

        $datas = array(
          'success' => false
          , 'message' => sprintf(_('Unable to create list %s'), $list_name)
          , 'list_id' => null
        );

        try
        {
          if (!$list_name)
          {
            throw new ControllerException(_('List name is required'));
          }

          $em = $app['Core']->getEntityManager();

          $List = new \Entities\UsrList();

          $Owner = new \Entities\UsrListOwner();
          $Owner->setRole(\Entities\UsrListOwner::ROLE_ADMIN);
          $Owner->setUser($app['Core']->getAuthenticatedUser());
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
        }
        catch (ControllerException $e)
        {
          $datas = array(
            'success' => false
            , 'message' => $e->getMessage()
          );
        }
        catch (\Exception $e)
        {

        }

        $Json = $app['Core']['Serializer']->serialize($datas, 'json');

        return new Response($Json, 200, array('Content-Type' => 'application/json'));
      }
    );

    /**
     * Gets a list
     */
    $controllers->get('/list/{list_id}/', function(Application $app, Request $request, $list_id)
      {

        $result = array();

        $user = $app['Core']->getAuthenticatedUser();
        $em   = $app['Core']->getEntityManager();

        $repository = $em->getRepository('\Entities\UsrList');

        $list = $repository->findUserListByUserAndId($user, $list_id);

        $entries = new \Doctrine\Common\Collections\ArrayCollection();
        $owners  = new \Doctrine\Common\Collections\ArrayCollection();

        foreach ($list->getOwners() as $owner)
        {
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

        foreach ($list->getEntries() as $entry)
        {
          $entries[] = array(
            'usr_id'       => $entry->getUser()->get_id(),
            'display_name' => $entry->getUser()->get_display_name(),
            'position'     => $entry->getUser()->get_position(),
            'job'          => $entry->getUser()->get_job(),
            'company'      => $entry->getUser()->get_company(),
            'email'        => $entry->getUser()->get_email(),
          );
        }


        /* @var $list \Entities\UsrList */
        $result = array(
          'id'      => $list->getId(),
          'name'    => $list->getName(),
          'created' => $list->getCreated()->format(DATE_ATOM),
          'updated' => $list->getUpdated()->format(DATE_ATOM),
          'owners'  => $owners,
          'users'   => $entries
        );


        return new Response($app['Core']->getTwig()->render('prod/actions/Feedback/list.html.twig', $result));
      }
    );

    /**
     * Update a list
     */
    $controllers->post('/list/{list_id}/update/', function(Application $app, $list_id)
      {
        $request = $app['request'];

        $datas = array(
          'success' => false
          , 'message' => _('Unable to update list')
        );

        try
        {
          $list_name = $request->get('name');

          if (!$list_name)
          {
            throw new ControllerException(_('List name is required'));
          }

          $user = $app['Core']->getAuthenticatedUser();
          $em   = $app['Core']->getEntityManager();

          $repository = $em->getRepository('\Entities\UsrList');

          $list = $repository->findUserListByUserAndId($user, $list_id);

          $list->setName($list_name);

          $em->merge($list);
          $em->flush();

          $datas = array(
            'success' => true
            , 'message' => _('List has been updated')
          );
        }
        catch (ControllerException $e)
        {
          $datas = array(
            'success' => false
            , 'message' => $e->getMessage()
          );
        }
        catch (\Exception $e)
        {

        }

        $Json = $app['Core']['Serializer']->serialize($datas, 'json');

        return new Response($Json, 200, array('Content-Type' => 'application/json'));
      }
    )->assert('list_id', '\d+');

    /**
     * Delete a list
     */
    $controllers->post('/list/{list_id}/delete/', function(Application $app, $list_id)
      {
        $em = $app['Core']->getEntityManager();

        try
        {
          $repository = $em->getRepository('\Entities\UsrList');

          $user = $app['Core']->getAuthenticatedUser();

          $list = $repository->findUserListByUserAndId($user, $list_id);

          $em->remove($list);
          $em->flush();

          $datas = array(
            'success' => true
            , 'message' => sprintf(_('List has been deleted'))
          );
        }
        catch (ControllerException $e)
        {
          $datas = array(
            'success' => false
            , 'message' => $e->getMessage()
          );
        }
        catch (\Exception $e)
        {

          $datas = array(
            'success' => false
            , 'message' => sprintf(_('Unable to delete list'))
          );
        }

        $Json = $app['Core']['Serializer']->serialize($datas, 'json');

        return new Response($Json, 200, array('Content-Type' => 'application/json'));
      }
    )->assert('list_id', '\d+');


    /**
     * Remove a usr_id from a list
     */
    $controllers->post('/list/{list_id}/remove/{usr_id}/', function(Application $app, $list_id, $usr_id)
      {
        $em = $app['Core']->getEntityManager();

        try
        {
          $repository = $em->getRepository('\Entities\UsrList');

          $user = $app['Core']->getAuthenticatedUser();

          $list = $repository->findUserListByUserAndId($user, $list_id);
          /* @var $list \Entities\UsrList */

          $entry_repository = $em->getRepository('\Entities\UsrListEntry');

          $user_entry = $entry_repository->findEntryByListAndUsrId($list, $usr_id);

          $em->remove($user_entry);
          $em->flush();

          $datas = array(
            'success' => true
            , 'message' => _('Entry removed from list')
          );
        }
        catch (ControllerException $e)
        {
          $datas = array(
            'success' => false
            , 'message' => $e->getMessage()
          );
        }
        catch (\Exception $e)
        {

          $datas = array(
            'success' => false
            , 'message' => _('Unable to remove entry from list ' . $e->getMessage())
          );
        }

        $Json = $app['Core']['Serializer']->serialize($datas, 'json');

        return new Response($Json, 200, array('Content-Type' => 'application/json'));
      }
    )->assert('list_id', '\d+')->assert('entry_id', '\d+');

    /**
     * Adds a usr_id to a list
     */
    $controllers->post('/list/{list_id}/add/', function(Application $app, Request $request, $list_id)
      {
        $em   = $app['Core']->getEntityManager();
        $user = $app['Core']->getAuthenticatedUser();



        try
        {
          if (!is_array($request->get('usr_ids')))
          {
            throw new Controller\Exception('Invalid or missing parameter usr_ids');
          }

          $repository = $em->getRepository('\Entities\UsrList');

          $list = $repository->findUserListByUserAndId($user, $list_id);
          /* @var $list \Entities\UsrList */

          $inserted_usr_ids = array();
          foreach ($request->get('usr_ids') as $usr_id)
          {
            $user_entry = \User_Adapter::getInstance($usr_id, \appbox::get_instance());

            if ($list->has($user_entry))
              continue;

            $entry = new \Entities\UsrListEntry();
            $entry->setUser($user_entry);
            $entry->setList($list);

            $list->addUsrListEntry($entry);

            $em->persist($entry);
            $em->merge($list);
            $inserted_usr_ids[] = $user_entry->get_id();
          }

          $em->flush();

          if (count($inserted_usr_ids) > 1)
          {
            $datas = array(
              'success' => true
              , 'message' => sprintf(_('%d Users added to list'), count($inserted_usr_ids))
              , 'result'  => $inserted_usr_ids
            );
          }
          else
          {
            $datas = array(
              'success' => true
              , 'message' => sprintf(_('%d User added to list'), count($inserted_usr_ids))
              , 'result'  => $inserted_usr_ids
            );
          }
        }
        catch (ControllerException $e)
        {
          $datas = array(
            'success' => false
            , 'message' => $e->getMessage()
          );
        }
        catch (\Exception $e)
        {

          $datas = array(
            'success' => false
            , 'message' => _('Unable to add usr to list')
          );
        }

        $Json = $app['Core']['Serializer']->serialize($datas, 'json');

        return new Response($Json, 200, array('Content-Type' => 'application/json'));
      }
    )->assert('list_id', '\d+')->assert('usr_id', '\d+');

    /**
     * Share a list to a user with an optionnal role
     */
    $controllers->post('/list/{list_id}/share/{usr_id}/', function(Application $app, $list_id, $usr_id)
      {
        $em   = $app['Core']->getEntityManager();
        $user = $app['Core']->getAuthenticatedUser();

        $availableRoles = array(
          \Entities\UsrListOwner::ROLE_USER,
          \Entities\UsrListOwner::ROLE_EDITOR,
          \Entities\UsrListOwner::ROLE_ADMIN,
        );

        if (!$app['request']->get('role'))
          throw new \Exception_BadRequest('Missing role parameter');
        elseif (!in_array($app['request']->get('role'), $availableRoles))
          throw new \Exception_BadRequest('Role is invalid');

        try
        {
          $repository = $em->getRepository('\Entities\UsrList');

          $list = $repository->findUserListByUserAndId($user, $list_id);
          /* @var $list \Entities\UsrList */

          if ($list->getOwner($user)->getRole() < \Entities\UsrListOwner::ROLE_EDITOR)
          {
            throw new \Exception('You are not authorized to do this');
          }

          $new_owner = \User_Adapter::getInstance($usr_id, \appbox::get_instance());

          if ($list->hasAccess($new_owner))
          {
            $owner = $list->getOwner($new_owner);
          }
          else
          {
            $owner = new \Entities\UsrListOwner();
            $owner->setList($list);
            $owner->setUser($new_owner);

            $list->addUsrListOwner($owner);

            $em->persist($owner);
            $em->merge($list);
          }

          $role = $app['request']->get('role');

          $owner->setRole($role);

          $em->merge($owner);
          $em->flush();

          $datas = array(
            'success' => true
            , 'message' => _('List shared to user')
          );
        }
        catch (ControllerException $e)
        {
          $datas = array(
            'success' => false
            , 'message' => $e->getMessage()
          );
        }
        catch (\Exception $e)
        {

          $datas = array(
            'success' => false
            , 'message' => _('Unable to share the list with the usr')
          );
        }

        $Json = $app['Core']['Serializer']->serialize($datas, 'json');

        return new Response($Json, 200, array('Content-Type' => 'application/json'));
      }
    );
    /**
     * UnShare a list to a user
     */
    $controllers->post('/list/{list_id}/unshare/{usr_id}/', function(Application $app, $list_id, $usr_id)
      {
        $em   = $app['Core']->getEntityManager();
        $user = $app['Core']->getAuthenticatedUser();

        try
        {
          $repository = $em->getRepository('\Entities\UsrList');

          $list = $repository->findUserListByUserAndId($user, $list_id);
          /* @var $list \Entities\UsrList */

          if ($list->getOwner($user)->getRole() < \Entities\UsrListOwner::ROLE_ADMIN)
          {
            throw new \Exception('You are not authorized to do this');
          }

          $owners_repository = $em->getRepository('\Entities\UsrListOwner');

          $owner = $owners_repository->findByListAndUsrId($list, $usr_id);

          $em->remove($owner);
          $em->flush();

          $datas = array(
            'success' => true
            , 'message' => _('Owner removed from list')
          );
        }
        catch (ControllerException $e)
        {
          $datas = array(
            'success' => false
            , 'message' => $e->getMessage()
          );
        }
        catch (\Exception $e)
        {
          $datas = array(
            'success' => false
            , 'message' => _('Unable to remove usr from list')
          );
        }

        $Json = $app['Core']['Serializer']->serialize($datas, 'json');

        return new Response($Json, 200, array('Content-Type' => 'application/json'));
      }
    );


    return $controllers;
  }

}
