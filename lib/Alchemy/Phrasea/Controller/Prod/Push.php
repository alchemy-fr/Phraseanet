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
class Push implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->get('/', function(Application $app)
            {
              $pusher = new RecordHelper\Push($app['Core']);

              $template = 'prod/actions/printer_default.html.twig';

              $twig = new \supertwig();

              return $twig->render($template, array('printer' => $printer, 'message' => ''));
            }
    );
    $controllers->post('/send/', function(Application $app)
            {
              $pusher = new RecordHelper\Push($app['Core']);
            }
    );

    $controllers->post('/validate/', function(Application $app)
            {
              $request = $app['request'];
      
              $pusher = new RecordHelper\Push($app['Core']);
              
              $em = $app['Core']->getEntityManager();
              
              $repository = $em->getRepository('\Entities\Basket');
              
              if($pusher->is_basket())
              {
                $Basket = $pusher->get_original_basket();
              }
              else
              {
                $Basket = new Basket();
                
                $em->persist($Basket);
                
                foreach($pusher->get_elements() as $element)
                {
                  $BasketElement = new BasketELement();
                  $BasketElement->setRecord($element);
                  $BasketElement->setBasket($Basket);
                  
                  $em->persist($BasketElement);
                  
                }
                
                $em->flush();
              }
              
              if(!$Basket->getValidation())
              {
                $Validation  = new \Entities\ValidationSession();
                $Validation->setInitiator($app['Core']->getAuthenticatedUser());
                $Validation->setBasket($Basket);
                
                $Basket->setValidation($Validation);
                
                $appbox = appbox::get_instance();
                
                foreach($request->get('participants') as $usr_id)
                {
                  $usr_id = \User_Adapter::getInstance($usr_id, $appbox);
                  $Participant = new \Entities\Participant();
                }
                
                $em->persist($Validation);
                
                $em->flush();
              }
              else
              {
                
              }
              
              
            }
    );

    $controllers->get('/search-user/', function(Application $app)
            {
              $request = $app['request'];

              $pusher = new RecordHelper\Push($app['Core']);

              $result = $pusher->search($request->get('query'));

              $datas = array();

              foreach ($result as $user)
              {
                $datas[] = array(
                    'type' => 'USER'
                    , 'usr_id' => $user->get_id()
                    , 'firstname'
                    , 'lastname'
                    , 'email'
                    , 'display_name'
                );
              }
            }
    );



    return $controllers;
  }

}
