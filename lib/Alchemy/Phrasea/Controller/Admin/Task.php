<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\Finder\Finder;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Task implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $appbox = \appbox::get_instance($app['phraseanet.core']);

        $controllers = $app['controllers_factory'];

        /*
         * route /admin/task/{id}/log
         *  show logs of a task
         */
        $controllers->get('/{id}/log', function(Application $app, Request $request, $id) use ($appbox) {
                $registry = $appbox->get_registry();
                $logdir = \p4string::addEndSlash($registry->get('GV_RootPath') . 'logs');

                $rname = '/task_' . $id . '((\.log)|(-.*\.log))$/';

                $finder = new Finder();
                $finder
                    ->files()->name($rname)
                    ->in($logdir)
                    //                   ->date('> now - 1 days')
                    ->sortByModifiedTime();

                $found = false;
                foreach ($finder->getIterator() as $file) {
                    // printf("%s <br/>\n", ($file->getRealPath()));
                    if ($request->get('clr') == $file->getFilename()) {
                        file_put_contents($file->getRealPath(), '');
                        $found = true;
                    }
                }
                if ($found) {
                    return $app->redirect(sprintf("/admin/task/%s/log", urlencode($id)));
                }

                return $app->stream(
                        function() use ($finder, $id) {
                            foreach ($finder->getIterator() as $file) {
                                printf("<h4>%s\n", $file->getRealPath());
                                printf("&nbsp;<a href=\"/admin/task/%s/log?clr=%s\">%s</a>"
                                    , $id
                                    , urlencode($file->getFilename())
                                    , _('Clear')
                                );
                                print("</h4>\n<pre>\n");
                                print(htmlentities(file_get_contents($file->getRealPath())));
                                print("</pre>\n");

                                ob_flush();
                                flush();
                            }
                        });
            });

        return $controllers;
    }
}
