<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MustacheLoader implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->get('/', function(Application $app, Request $request) {
            $template_name = $request->query->get('template');

            if (!preg_match('/^[a-zA-Z0-9-_]+$/', $template_name)) {
                throw new \Exception_BadRequest('Wrong template name : ' . $template_name);
            }

            $template_path = realpath(__DIR__ . '/../../../../../templates/web/Mustache/Prod/' . $template_name . '.Mustache.html');

            if (!file_exists($template_path)) {
                throw new \Exception_NotFound('Template does not exists : ' . $template_path);
            }

            return new \Symfony\Component\HttpFoundation\Response(file_get_contents($template_path));
        });

        return $controllers;
    }
}
