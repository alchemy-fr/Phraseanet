<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class Controller_Prod_Records_MoveCollection implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->post('/', function() use ($app)
            {
              $request = $app['request'];
              $move = new module_prod_route_records_move($request);
              $move->propose();

              $template = 'prod/actions/collection_default.twig';
              $twig = new supertwig();
              $twig->addFilter(array('bas_names' => 'phrasea::bas_names'));

              return $twig->render($template, array('action' => $move, 'message' => ''));
            }
    );


    $controllers->post('/apply/', function() use ($app)
            {
              $request = $app['request'];
              $move = new module_prod_route_records_move($request);
              $move->execute($request);
              $template = 'prod/actions/collection_submit.twig';

              $twig = new supertwig();
              $twig->addFilter(array('bas_names' => 'phrasea::bas_names'));

              return $twig->render($template, array('action' => $move, 'message' => ''));
            });

    return $controllers;
  }

}