<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report\Controller;

use Alchemy\Phrasea\Application\Helper\DelivererAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Api\Result;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\ExportEvent;
use Alchemy\Phrasea\Core\Event\OrderEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Http\DeliverDataInterface;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Order\OrderElementTransformer;
use Alchemy\Phrasea\Order\OrderFiller;
use Alchemy\Phrasea\Order\OrderTransformer;
use Alchemy\Phrasea\Order\OrderViewBuilder;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Doctrine\Common\Collections\ArrayCollection;
use League\Fractal\Manager;
use League\Fractal\Pagination\PagerfantaPaginatorAdapter;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\ResourceInterface;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiReportController extends BaseReportController
{
    use DelivererAware;
    use FilesystemAware;
    use JsonBodyAware;

    public function rootAction(Request $request)
    {
        $ret = [
            'granted' => $this->getGranted()
        ];

        $result = Result::create($request, $ret);

        return $result->createResponse();
    }

    public function connectionsAction(Request $request, $sbasId)
    {
        $ret = [
            'connections' => $this->getConnections($request, $sbasId)
        ];

        $result = Result::create($request, $ret);

        return $result->createResponse();
    }

}
