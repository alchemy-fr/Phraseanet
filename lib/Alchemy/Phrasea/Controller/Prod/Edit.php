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
/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

class Edit implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->post('/', function() use ($app)
            {
              $request = $app['request'];
              
              $handler = new \Alchemy\Phrasea\RequestHandler\RecordsEdit($request);
              
              $handler->propose_editing();

              $template = 'prod/actions/edit_default.twig';

              $twig = new \supertwig();
              $twig->addFilter(array('sbas_names' => 'phrasea::sbas_names'));

              return $twig->render($template, array('edit' => $handler, 'message' => ''));
            }
    );

    $controllers->post('/apply/', function() use ($app)
            {
              $request = $app['request'];
              $editing = new \Alchemy\Phrasea\RequestHandler\RecordsEdit($request);
              $editing->execute($request);

              $template = 'prod/actions/edit_default.twig';

              $twig = new \supertwig();
              $twig->addFilter(array('sbas_names' => 'phrasea::sbas_names'));

              return $twig->render($template, array('edit' => $editing, 'message' => ''));
            }
    );
    
    return $controllers;
  }

}
