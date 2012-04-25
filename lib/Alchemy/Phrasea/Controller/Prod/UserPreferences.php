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
use Alchemy\Phrasea\RouteProcessor\Basket as BasketRoute,
    Alchemy\Phrasea\Helper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UserPreferences implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = new ControllerCollection();

        $controllers->post('/save/', function(Application $app, Request $request) {
                $ret = array('success' => false, 'message' => _('Error while saving preference'));

                try {
                    $user = $app['Core']->getAuthenticatedUser();

                    $ret = $user->setPrefs($request->get('prop'), $request->get('value'));

                    if ($ret == $request->get('value'))
                        $output = "1";
                    else
                        $output = "0";

                    $ret = array('success' => true, 'message' => _('Preference saved !'));
                } catch (\Exception $e) {

                }

                $Serializer = $app['Core']['Serializer'];
                $datas = $Serializer->serialize($ret, 'json');

                return new Response($datas, 200, array('Content-Type' => 'application/json'));
            });

        return $controllers;
    }
}
