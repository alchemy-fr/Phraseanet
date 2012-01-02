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

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Alchemy\Phrasea\Helper\Record as RecordHelper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MoveCollection implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->post('/', function() use ($app)
            {
              $request = $app['request'];
              $move = new RecordHelper\MoveCollection($app['Core']);
              $move->propose();

              $template = 'prod/actions/collection_default.twig';
              $twig = new \supertwig();
              $twig->addFilter(array('bas_names' => 'phrasea::bas_names'));

              return $twig->render($template, array('action' => $move, 'message' => ''));
            }
    );


    $controllers->post('/apply/', function() use ($app)
            {
              $request = $app['request'];
              $move = new RecordHelper\MoveCollection($app['Core']);
              $move->execute($request);
              $template = 'prod/actions/collection_submit.twig';

              $twig = new \supertwig();
              $twig->addFilter(array('bas_names' => 'phrasea::bas_names'));

              return $twig->render($template, array('action' => $move, 'message' => ''));
            });

    return $controllers;
  }

}