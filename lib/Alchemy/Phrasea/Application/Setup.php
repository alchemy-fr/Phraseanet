<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Alchemy\Phrasea\Controller\Setup as Controller;
use Alchemy\Phrasea\Controller\Utils as ControllerUtils;

require_once __DIR__ . '/../../../bootstrap.php';
/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(function()
                {
                  $app = new Silex\Application();

                  $app['Core'] = bootstrap::getCore();

                  $app['install'] = false;
                  $app['upgrade'] = false;

                  $app->before(function($a) use ($app)
                          {
                            if (setup::is_installed())
                            {
                              $appbox = appbox::get_instance();

                              if (!$appbox->need_major_upgrade())
                              {
                                throw new Exception_Setup_PhraseaAlreadyInstalled();
                              }

                              $app['upgrade'] = true;
                            }
                            elseif (\setup::needUpgradeConfigurationFile())
                            {
                              $registry = \registry::get_instance();

                              //copy config sample
                              $configSampleFile = __DIR__ . "/../../../../config/config.sample.yml";
                              $configFile = __DIR__ . "/../../../../config/config.yml";

                              if (!copy($configSampleFile, $configFile))
                              {
                                throw new \Exception(sprintf("Unable to copy %s", $configSampleFile));
                              }

                              //copy service sample
                              $serviceSampleFile = __DIR__ . "/../../../../config/services.sample.yml";
                              $serviceFile = __DIR__ . "/../../../../config/services.yml";

                              if (!copy($serviceSampleFile, $serviceFile))
                              {
                                throw new \Exception(sprintf("Unable to copy %s", $serviceSampleFile));
                              }
                              
                              //copy connexion sample
                              $connexionSampleFile = __DIR__ . "/../../../../config/connexions.sample.yml";
                              $connexionFile = __DIR__ . "/../../../../config/connexions.yml";

                              if (!copy($connexionSampleFile, $connexionFile))
                              {
                                throw new \Exception(sprintf("Unable to copy %s", $connexionFile));
                              }

                              //get configuration object
                              $appConf = new \Alchemy\Phrasea\Core\Configuration\Application();
                              $parser = new \Alchemy\Phrasea\Core\Configuration\Parser\Yaml();
                              $handler = new \Alchemy\Phrasea\Core\Configuration\Handler($appConf, $parser);
                              $configuration = new \Alchemy\Phrasea\Core\Configuration($handler);

                              //refactor credentials
                              $connexionINI = array();

                              require __DIR__ . "/../../../../config/connexion.inc";

                              $connexionINI['host'] = $hostname;
                              $connexionINI['port'] = $port;
                              $connexionINI['user'] = $user;
                              $connexionINI['password'] = $password;
                              $connexionINI['dbname'] = $dbname;
                              $connexionINI['driver'] = 'pdo_mysql';
                              $connexionINI['charset'] = 'UTF8';

                              $request = $app["request"];
                              //write servername
                              $serverName = $request->getScheme() . '://' . $request->getHttpHost() . '/';

                              //write credentials to connexion file
                              $connexionFile = $appConf->getConnexionFile();

                              $connexion = array(
                                  'main_connexion' => $connexionINI,
                                  'test_connexion' => array(
                                      'driver' => 'pdo_sqlite',
                                      'path' => $registry->get("GV_RootPath") . 'lib/unitTest/tests.sqlite',
                                      'charset' => 'UTF8'
                                      ));

                              $yaml = $configuration->getConfigurationHandler()->getParser()->dump($connexion, 2);

                              if (!file_put_contents($connexionFile->getPathname(), $yaml) !== false)
                              {
                                throw new \Exception(sprintf(_('Impossible d\'ecrire dans le fichier %s'), $connexionFile->getPathname()));
                              }

                              //rewrite service file
                              $serviceFile = $appConf->getServiceFile();
                              $service = $configuration->getConfigurationHandler()->getParser()->parse($serviceFile);
                              $yaml = $configuration->getConfigurationHandler()->getParser()->dump($service, 5);

                              if (!file_put_contents($serviceFile->getPathname(), $yaml) !== false)
                              {
                                throw new \Exception(sprintf(_('Impossible d\'ecrire dans le fichier %s'), $serviceFile->getPathname()));
                              }
                              $arrayConf = $configuration->all();

                              //rewrite main conf
                              foreach ($arrayConf as $key => $value)
                              {
                                if (is_array($value) && array_key_exists('phraseanet', $value))
                                {
                                  $arrayConf[$key]["phraseanet"]["servername"] = $serverName;
                                }
                              }

                              $configuration->write($arrayConf);

                              $app->redirect("/setup/installer/");
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


                  $app->mount('/installer/', new Controller\Installer());
                  $app->mount('/upgrader/', new Controller\Upgrader());
                  $app->mount('/test', new ControllerUtils\PathFileTest());
                  $app->mount('/connection_test', new ControllerUtils\ConnectionTest());

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