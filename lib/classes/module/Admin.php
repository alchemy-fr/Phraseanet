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

return call_user_func(
                function()
                {
                  $app = new Application();

                  $app->mount('/publications', new Controller_Admin_Publications());
                  $app->mount('/users', new Controller_Admin_Users());
                  $app->mount('/fields', new Controller_Admin_Fields());
                  $app->mount('/tests/connection', new Controller_Utils_ConnectionTest());
                  $app->mount('/tests/pathurl', new Controller_Utils_PathFileTest());

                  $app->error(function(\Exception $e)
                          {
                            return $e->getMessage();
                          });

                  return $app;
                });
