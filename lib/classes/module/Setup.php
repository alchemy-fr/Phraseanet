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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

return call_user_func(function()
                {
                  $app = new Silex\Application();

                  $app['install'] = false;
                  $app['upgrade'] = false;

                  $app->before(function($a) use ($app)
                          {
                            if (setup::is_installed())
                            {
                              $appbox = appbox::get_instance();

                              if (!$appbox->need_major_upgrade())
                                throw new Exception_Setup_PhraseaAlreadyInstalled();

                              $app['upgrade'] = true;
                            }
                            else
                            {
                              $app['install'] = true;
                            }

                            return;
                          });


                  $app->get('/', function() use ($app)
                          {
                            if ($app['install'] === true)
                              return $app->redirect('/setup/installer/');
                            if ($app['upgrade'] === true)
                              return $app->redirect('/setup/upgrader/');
                          });


                  $app->mount('/installer/', new Controller_Setup_Installer());
                  $app->mount('/upgrader/', new Controller_Setup_Upgrader());
                  $app->mount('/test', new Controller_Utils_PathFileTest());
                  $app->mount('/connection_test', new Controller_Utils_ConnectionTest());

                  $app->error(function($e) use ($app)
                          {
                            if ($e instanceof Exception_Setup_PhraseaAlreadyInstalled)
                              return $app->redirect('/login');

                            return new Response(
                                            sprintf(
                                                    'Error %s @%s:%s'
                                                    , $e->getFile()
                                                    , $e->getLine()
                                                    , $e->getMessage()
                                            )
                                            , 500
                            );
                          });

                  return $app;
                });