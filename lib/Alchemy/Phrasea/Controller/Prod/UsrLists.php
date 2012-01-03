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
    Alchemy\Phrasea\Out\Module\PDF as PDFExport;
use Symfony\Component\HttpFoundation\Response,
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
    $controllers->get('/list/all/', function() use ($app)
            {
              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\UsrList');

              $lists = $repository->findUserLists($app['Core']->getAuthenticatedUser());

              $datas = array('lists' => array());

              foreach ($lists as $list)
              {

                $owners = $entries = array();

                foreach ($list->getOwners() as $owner)
                {
                  $owners[] = array(
                      'usr_id' => $owner->getUser()->get_id(),
                      'display_name' => $owner->getUser()->get_display_name(),
                      'position' => $owner->getUser()->get_position(),
                      'job' => $owner->getUser()->get_job(),
                      'company' => $owner->getUser()->get_company(),
                      'email' => $owner->getUser()->get_email(),
                      'role' => $owner->getRole()
                  );
                }

                foreach ($list->getUsers() as $entry)
                {
                  $entries[] = array(
                      'usr_id' => $owner->getUser()->get_id(),
                      'display_name' => $owner->getUser()->get_display_name(),
                      'position' => $owner->getUser()->get_position(),
                      'job' => $owner->getUser()->get_job(),
                      'company' => $owner->getUser()->get_company(),
                      'email' => $owner->getUser()->get_email(),
                  );
                }


                /* @var $list \Entities\UsrList */
                $datas['lists'][] = array(
                    'name' => $list->getName(),
                    'created' => $list->getCreated()->format(DATE_ATOM),
                    'updated' => $list->getUpdated()->format(DATE_ATOM),
                    'owners' => $owners,
                    'users' => $entries
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Creates a list
     */
    $controllers->post('/list/', function() use ($app)
            {
              $request = $app['request'];

              $list_name = $request->get('name');

              try
              {
                $em = $app['Core']->getEntityManager();

                $repository = $em->getRepository('\Entities\Usr');

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
                    , 'message' => ''
                );
              }
              catch (\Exception $e)
              {

                $datas = array(
                    'success' => false
                    , 'message' => sprintf(_('Unable to create list %s'), $list_name)
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Gets a list
     */
    $controllers->get('/list/{list_id}/', function() use ($app)
            {
              $user = $app['Core']->getAuthenticatedUser();
              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\UsrList');

              $list = $repository->findUserListByUserAndId($user, $list_id);

              $owners = $entries = $lists = array();

              foreach ($list->getOwners() as $owner)
              {
                $owners[] = array(
                    'usr_id' => $owner->getUser()->get_id(),
                    'display_name' => $owner->getUser()->get_display_name(),
                    'position' => $owner->getUser()->get_position(),
                    'job' => $owner->getUser()->get_job(),
                    'company' => $owner->getUser()->get_company(),
                    'email' => $owner->getUser()->get_email(),
                    'role' => $owner->getRole()
                );
              }

              foreach ($list->getUsers() as $entry)
              {
                $entries[] = array(
                    'usr_id' => $owner->getUser()->get_id(),
                    'display_name' => $owner->getUser()->get_display_name(),
                    'position' => $owner->getUser()->get_position(),
                    'job' => $owner->getUser()->get_job(),
                    'company' => $owner->getUser()->get_company(),
                    'email' => $owner->getUser()->get_email(),
                );
              }


              /* @var $list \Entities\UsrList */
              $datas = array('list' => array(
                      'name' => $list->getName(),
                      'created' => $list->getCreated()->format(DATE_ATOM),
                      'updated' => $list->getUpdated()->format(DATE_ATOM),
                      'owners' => $owners,
                      'users' => $entries
                  )
              );

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Update a list
     */
    $controllers->post('/list/{list_id}/update/', function() use ($app)
            {
              $user = $app['Core']->getAuthenticatedUser();
              $em = $app['Core']->getEntityManager();

              try
              {
                $request = $app['request'];

                $repository = $em->getRepository('\Entities\UsrList');

                $list = $repository->findUserListByUserAndId($user, $list_id);

                $list->setName($request->get('name'));

                $em->merge($list);
                $em->flush();
                
                $datas = array(
                    'success' => true
                    , 'message' => ''
                );
              }
              catch (\Exception $e)
              {

                $datas = array(
                    'success' => false
                    , 'message' => sprintf(_('Unable to create list %s'), $list_name)
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );

    /**
     * Delete a list
     */
    $controllers->post('/list/{list_id}/delete/', function() use ($app)
            {
              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\Usr');
              
              try
              {
                $repository = $em->getRepository('\Entities\UsrList');

                $list = $repository->findUserListByUserAndId($user, $list_id);

                $em->remove($list);
                $em->flush();
              }
              catch (\Exception $e)
              {

                $datas = array(
                    'success' => false
                    , 'message' => sprintf(_('Unable to create list %s'), $list_name)
                );
              }

              $Json = $app['Core']['Serializer']->serialize($datas, 'json');

              return new Response($Json, 200, array('Content-Type' => 'application/json'));
            }
    );


    /**
     * Remove a usr_id from a list
     */
    $controllers->post('/list/{list_id}/remove/{usr_id}/', function() use ($app)
            {
              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\Usr');
            }
    );

    /**
     * Adds a usr_id to a list
     */
    $controllers->post('/list/{list_id}/add/{usr_id}/', function() use ($app)
            {
              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\Usr');
            }
    );

    /**
     * Share a list to a user with an optionnal role
     */
    $controllers->post('/list/{list_id}/share/{usr_id}/', function() use ($app)
            {
              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\Usr');
            }
    );
    /**
     * UnShare a list to a user 
     */
    $controllers->post('/list/{list_id}/unshare/{usr_id}/', function() use ($app)
            {
              $em = $app['Core']->getEntityManager();

              $repository = $em->getRepository('\Entities\Usr');
            }
    );


    return $controllers;
  }

}
