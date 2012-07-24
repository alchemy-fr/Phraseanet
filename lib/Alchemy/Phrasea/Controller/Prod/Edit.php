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
use Alchemy\Phrasea\Helper\Record as RecordHelper;
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Edit implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->post('/', function(Application $app, Request $request) {
                $handler = new RecordHelper\Edit($app['Core'], $request);

                $handler->propose_editing();

                $template = 'prod/actions/edit_default.twig';

                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                return $twig->render($template, array('edit'    => $handler, 'message' => ''));
            }
        );

        $controllers->get('/vocabulary/{vocabulary}/', function(Application $app, Request $request, $vocabulary) {
                $datas = array('success' => false, 'message' => '', 'results' => array());

                $Serializer = $app['Core']['Serializer'];

                $sbas_id = (int) $request->get('sbas_id');

                try {
                    if ($sbas_id === 0) {
                        throw new \Exception('Invalid sbas_id');
                    }

                    $VC = \Alchemy\Phrasea\Vocabulary\Controller::get($vocabulary);
                    $databox = \databox::get_instance($sbas_id);
                } catch (\Exception $e) {
                    $datas['message'] = _('Vocabulary not found');

                    $datas = $Serializer->serialize($datas, 'json');

                    return new response($datas, 200, array('Content-Type' => 'application/json'));
                }

                $query = $request->get('query');

                $results = $VC->find($query, $app['Core']->getAuthenticatedUser(), $databox);

                $list = array();

                foreach ($results as $Term) {
                    /* @var $Term \Alchemy\Phrasea\Vocabulary\Term */
                    $list[] = array(
                        'id'      => $Term->getId(),
                        'context' => $Term->getContext(),
                        'value'   => $Term->getValue(),
                    );
                }

                $datas['success'] = true;
                $datas['results'] = $list;

                return new response($Serializer->serialize($datas, 'json'), 200, array('Content-Type' => 'application/json'));
            }
        );

        $controllers->post('/apply/', function(Application $app, Request $request) {
                $editing = new RecordHelper\Edit($app['Core'], $app['request']);
                $editing->execute($request);

                $template = 'prod/actions/edit_default.twig';

                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                return $twig->render($template, array('edit'    => $editing, 'message' => ''));
            }
        );

        return $controllers;
    }
}
