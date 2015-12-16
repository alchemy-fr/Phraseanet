<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Api;

use Alchemy\Phrasea\Application\Helper\DataboxLoggerAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\Basket;
use Symfony\Component\HttpFoundation\Request;

class BasketController extends Controller
{
    use DataboxLoggerAware;
    use DispatcherAware;

    public function addRecordsAction(Request $request, Basket $basket)
    {
    }

    public function removeRecordsAction(Request $request, Basket $basket)
    {
    }

    public function reorderRecordsAction(Request $request, Basket $basket)
    {
    }
}
