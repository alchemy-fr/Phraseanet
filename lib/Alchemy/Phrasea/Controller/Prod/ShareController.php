<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareController extends Controller
{
    /**
     *  Share a record
     *
     * @param  Application $app
     * @param  Request     $request
     * @param  integer     $base_id
     * @param  integer     $record_id
     * @return Response
     */
    public function shareRecord(Application $app, Request $request, $base_id, $record_id)
    {
        $record = new \record_adapter($app, \phrasea::sbasFromBas($app, $base_id), $record_id);

        if (!$app['acl']->get($app['authentication']->getUser())->has_access_to_subdef($record, 'preview')) {
            $app->abort(403);
        }

        return new Response($app['twig']->render('prod/Share/record.html.twig', [
            'record' => $record,
        ]));
    }
}
