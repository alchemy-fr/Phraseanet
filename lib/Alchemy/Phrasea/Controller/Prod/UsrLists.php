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
use Symfony\Component\HttpFoundation\Response;

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
      
            }
    );
    
    /**
     * Creates a list
     */
    $controllers->post('/list/{list_id}/', function() use ($app)
            {
      
            }
    );
    
    /**
     * Gets a list
     */
    $controllers->get('/list/{list_id}/', function() use ($app)
            {
      
            }
    );

    /**
     * Update a list
     */
    $controllers->get('/list/{list_id}/update/', function() use ($app)
            {
      
            }
    );
    
    /**
     * Delete a list
     */
    $controllers->get('/list/{list_id}/delete/', function() use ($app)
            {
      
            }
    );

    
    /**
     * Remove a usr_id from a list
     */
    $controllers->post('/list/{list_id}/remove/{usr_id}/', function() use ($app)
            {
      
            }
    );
    
    /**
     * Adds a usr_id to a list
     */
    $controllers->post('/list/{list_id}/add/{usr_id}/', function() use ($app)
            {
      
            }
    );
    
    /**
     * Share a list to a user with an optionnal role
     */
    $controllers->post('/list/{list_id}/share/{usr_id}/', function() use ($app)
            {
      
            }
    );
    /**
     * UnShare a list to a user 
     */
    $controllers->post('/list/{list_id}/unshare/{usr_id}/', function() use ($app)
            {
      
            }
    );


    return $controllers;
  }
}