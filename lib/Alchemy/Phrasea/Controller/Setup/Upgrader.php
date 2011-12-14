<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Setup;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

class Upgrader implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $app['registry'] = new \Setup_Registry();
    $app['available_languages'] = \User_Adapter::detectLanguage($app['registry']);
    $app['twig'] = function()
            {
              return new \supertwig();
            };

    $controllers->get('/', function() use ($app)
            {
              require_once dirname(__FILE__) . '/../../../../bootstrap.php';
              $upgrade_status = \Setup_Upgrade::get_status();

              ini_set('display_errors', 'on');
              $html = $app['twig']->render(
                      '/setup/upgrader.twig'
                      , array(
                  'locale' => \Session_Handler::get_locale()
                  , 'upgrade_status' => $upgrade_status
                  , 'available_locales' => $app['available_languages']
                  , 'bad_users' => \User_Adapter::get_wrong_email_users(\appbox::get_instance())
                  , 'version_number' => GV_version
                  , 'version_name' => GV_version_name)
              );
              ini_set('display_errors', 'on');

              return new Response($html);
            });

    $controllers->get('/status/', function() use ($app)
            {
              require_once dirname(__FILE__) . '/../../../../bootstrap.php';
              ini_set('display_errors', 'on');

              $datas = \Setup_Upgrade::get_status();

              return new Response(\p4string::jsonencode($datas), 200, array('Content-Type: application/json'));
            });

    $controllers->post('/execute/', function() use ($app)
            {
              require_once dirname(__FILE__) . '/../../../../bootstrap.php';
              ini_set('display_errors', 'on');
              set_time_limit(0);
              session_write_close();
              ignore_user_abort(true);

              $appbox = \appbox::get_instance();
              $upgrader = new \Setup_Upgrade($appbox);
              $appbox->forceUpgrade($upgrader);

              return;
            });

    return $controllers;
  }

}