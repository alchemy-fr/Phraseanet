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

    $controllers->get('/', function() use ($app)
            {
              require_once __DIR__ . '/../../../../bootstrap.php';
              $upgrade_status = \Setup_Upgrade::get_status();

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();
              
              ini_set('display_errors', 'on');
              $html = $twig->render(
                      '/setup/upgrader.html.twig'
                      , array(
                  'locale' => \Session_Handler::get_locale()
                  , 'upgrade_status' => $upgrade_status
                  , 'available_locales' => $app['Core']::getAvailableLanguages()
                  , 'bad_users' => \User_Adapter::get_wrong_email_users(\appbox::get_instance())
                  , 'version_number' => $app['Core']['Version']->getNumber()
                  , 'version_name' => $app['Core']['Version']->getName()
                      )
              );
              ini_set('display_errors', 'on');

              return new Response($html);
            });

    $controllers->get('/status/', function() use ($app)
            {
              require_once __DIR__ . '/../../../../bootstrap.php';
              ini_set('display_errors', 'on');

              $datas = \Setup_Upgrade::get_status();

              return new Response(\p4string::jsonencode($datas), 200, array('Content-Type: application/json'));
            });

    $controllers->post('/execute/', function() use ($app)
            {
              require_once __DIR__ . '/../../../../bootstrap.php';
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