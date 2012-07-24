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
use Silex\ControllerCollection;
use Alchemy\Phrasea\Helper\Record\MoveCollection as Helper;
use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Helper\Record as RecordHelper;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class MoveCollection implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->post('/', $this->call('displayForm'));
        $controllers->post('/apply/', $this->call('apply'));

        return $controllers;
    }

    public function displayForm(Application $app, Request $request)
    {
        $move = new Helper($app['phraseanet.core'], $request);
        $move->propose();

        return $app['twig']->render('prod/actions/collection_default.html.twig', array('action'  => $move, 'message' => ''));
    }

    public function apply(Application $app, Request $request)
    {
        $move = new Helper($app['phraseanet.core'], $request);
        $success = false;

        try {
            $move->execute();
            $success = true;
            $msg = _('Records have been successfuly moved');
        } catch (\Exception_Unauthorized $e) {
            $msg = sprintf(_("You do not have the permission to move records to %s"), \phrasea::bas_names($move->getBaseIdDestination()));
        } catch (\Exception $e) {
            $msg = _('An error occured');
        }

        $datas = array(
            'success' => $success,
            'message' => $msg
        );

        return $app->json($datas);
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
