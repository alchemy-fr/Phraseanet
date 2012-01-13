<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Silex\Application;
use Alchemy\Phrasea\Controller\Admin as Controller;
use Alchemy\Phrasea\Controller\Utils as ControllerUtils;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
return call_user_func(
                function()
                {
                  $app = new Application();

                  $app['Core'] = bootstrap::getCore();
                  
                  $app["debug"]  = $app["Core"]->getConfiguration()->isDebug();
                  
                  $app->mount('/publications', new Controller\Publications());
                  $app->mount('/users', new Controller\Users());
                  $app->mount('/fields', new Controller\Fields());
                  $app->mount('/tests/connection', new ControllerUtils\ConnectionTest());
                  $app->mount('/tests/pathurl', new ControllerUtils\PathFileTest());

                  $app->error(function(\Exception $e)
                          {
                            return $e->getMessage();
                          });

                  return $app;
                });