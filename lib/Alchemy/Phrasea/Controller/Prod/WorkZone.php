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
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpKernel\Exception\HttpException,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\RouteProcessor\WorkZone as RouteWorkZone,
    Alchemy\Phrasea\Helper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class WorkZone implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->get('/', function(Application $app)
            {
              $twig = new \supertwig();

              $params = array(
                  'WorkZone' => new Helper\WorkZone($app['Core'])
                  , 'selected_type' => $app['request']->get('type')
                  , 'selected_id' => $app['request']->get('id')
                  , 'srt' => $app['request']->get('sort')
              );

              $twig->addFilter(array('get_collection_logo' => 'collection::getLogo'));

              return new Response($twig->render('prod/baskets.html', $params));
            });

    return $controllers;
  }

}
