<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Manipulator\LazaretManipulator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LazaretController extends Controller
{
    /**
     * @param int     $lazaret_id
     * @param Request $request
     * @return Response
     */
    public function quarantineItemDeleteAction(Request $request, $lazaret_id)
    {
        /** @var LazaretManipulator $lazaretManipulator */
        $lazaretManipulator = $this->app['manipulator.lazaret'];

        $ret = $lazaretManipulator->deny($lazaret_id);

        return Result::create($request, $ret)->createResponse();
    }

    public function quarantineItemAddAction(Request $request, $lazaret_id)
    {
        /** @var LazaretManipulator $lazaretManipulator */
        $lazaretManipulator = $this->app['manipulator.lazaret'];

        $ret = $lazaretManipulator->add($lazaret_id);

        return Result::create($request, $ret)->createResponse();
    }

    public function quarantineItemEmptyAction(Request $request)
    {
        $maxTodo = -1;  // all
        if($request->get('max') !== null) {
            $maxTodo = (int)($request->get('max'));
        }
        if( $maxTodo <= 0) {
            $maxTodo = -1;      // all
        }

        /** @var LazaretManipulator $lazaretManipulator */
        $lazaretManipulator = $this->app['manipulator.lazaret'];

        $ret = $lazaretManipulator->clear($maxTodo);

        return Result::create($request, $ret)->createResponse();
    }
}
