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
use Alchemy\Phrasea\Helper\Record as RecordHelper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Edit implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->post('/', function(Application $app, Request $request)
            {
              $handler = new RecordHelper\Edit($app['Core'], $request);

              $handler->propose_editing();

              $template = 'prod/actions/edit_default.twig';

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render($template, array('edit' => $handler, 'message' => ''));
            }
    );

    $controllers->post('/apply/', function(Application $app, Request $request) 
            {
              $editing = new RecordHelper\Edit($app['Core'], $app['request']);
              $editing->execute($request);

              $template = 'prod/actions/edit_default.twig';

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render($template, array('edit' => $editing, 'message' => ''));
            }
    );

    return $controllers;
  }

}
