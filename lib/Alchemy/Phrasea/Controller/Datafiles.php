<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Datafiles extends AbstractDelivery
{
    public function connect(Application $app)
    {
        $app['controller.datafiles'] = $this;

        $controllers = $app['controllers_factory'];

        $that = $this;

        $controllers->before(function (Request $request) use ($app) {
            if (!$app['authentication']->isAuthenticated()) {
                $app->abort(403, sprintf('You are not authorized to access %s', $request->getRequestUri()));
            }
        });

        $controllers->get('/{sbas_id}/{record_id}/{subdef}/', function ($sbas_id, $record_id, $subdef, PhraseaApplication $app) use ($that) {
            $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
            $record = new \record_adapter($app, $sbas_id, $record_id);

            $stamp = $watermark = false;

            if ($subdef != 'thumbnail') {
                $all_access = false;
                $subdefStruct = $databox->get_subdef_structure();

                if ($subdefStruct->getSubdefGroup($record->get_type())) {
                    foreach ($subdefStruct->getSubdefGroup($record->get_type()) as $subdefObj) {
                        if ($subdefObj->get_name() == $subdef) {
                            if ($subdefObj->get_class() == 'thumbnail') {
                                $all_access = true;
                            }
                            break;
                        }
                    }
                }

                if (!$record->has_subdef($subdef) || !$record->get_subdef($subdef)->is_physically_present()) {
                    throw new NotFoundHttpException;
                }

                if (!$app['acl']->get($app['authentication']->getUser())->has_access_to_subdef($record, $subdef)) {
                    throw new AccessDeniedHttpException(sprintf('User has not access to subdef %s', $subdef));
                }

                $stamp = false;
                $watermark = !$app['acl']->get($app['authentication']->getUser())->has_right_on_base($record->get_base_id(), 'nowatermark');

                if ($watermark && !$all_access) {
                    $subdef_class = $databox
                        ->get_subdef_structure()
                        ->get_subdef($record->get_type(), $subdef)
                        ->get_class();

                    if ($subdef_class == \databox_subdef::CLASS_PREVIEW && $app['acl']->get($app['authentication']->getUser())->has_preview_grant($record)) {
                        $watermark = false;
                    } elseif ($subdef_class == \databox_subdef::CLASS_DOCUMENT && $app['acl']->get($app['authentication']->getUser())->has_hd_grant($record)) {
                        $watermark = false;
                    }
                }

                if ($watermark && !$all_access) {

                    $repository = $app['EM']->getRepository('Phraseanet:BasketElement');

                    /* @var $repository BasketElementRepository */

                    $ValidationByRecord = $repository->findReceivedValidationElementsByRecord($record, $app['authentication']->getUser());
                    $ReceptionByRecord = $repository->findReceivedElementsByRecord($record, $app['authentication']->getUser());

                    if ($ValidationByRecord && count($ValidationByRecord) > 0) {
                        $watermark = false;
                    } elseif ($ReceptionByRecord && count($ReceptionByRecord) > 0) {
                        $watermark = false;
                    }
                }
            }

            return $that->deliverContent($app['request'], $record, $subdef, $watermark, $stamp, $app);
        })
            ->bind('datafile')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        return $controllers;
    }
}
