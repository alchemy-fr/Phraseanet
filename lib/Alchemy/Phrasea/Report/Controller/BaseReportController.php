<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Report\Controller;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\OrderDeliveryEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\OrderElementRepository;
use Alchemy\Phrasea\Model\Repositories\OrderRepository;
use Alchemy\Phrasea\Order\OrderBasketProvider;
use Alchemy\Phrasea\Order\OrderDelivery;
use Alchemy\Phrasea\Order\OrderValidator;
use Alchemy\Phrasea\Order\PartialOrder;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Assert\Assertion;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;



class BaseReportController extends Controller
{
    use JsonBodyAware;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        //$id = $this->getAuthenticator()->getUser()->getId();
        //$app->getAuthenticatedUser();
        // $this->getAuthenticatedUser()->isAdmin();
    }

    protected function getGranted()
    {
        $granted = [];

        foreach ($this->getAclForUser()->get_granted_base([\ACL::CANREPORT]) as $collection) {
            if (!isset($granted[$collection->get_sbas_id()])) {
                $granted[$collection->get_sbas_id()] = [
                    'id' => $collection->get_sbas_id(),
                    'name' => $collection->get_databox()->get_viewname(),
                    'collections' => []
                ];
            }
            $granted[$collection->get_sbas_id()]['collections'][] = [
                'id' => $collection->get_coll_id(),
                'base_id' => $collection->get_base_id(),
                'name' => $collection->get_name()
            ];
        }

        return $granted;
    }

    protected function  getConnections(Request $request, $sbasId)
    {
        $parms = [];
        $group = $request->get('group');
        switch($group) {
            case null:
                $sql = "SELECT * FROM `log`\n"
                    . " WHERE {{GlobalFilter}}";
                break;
            case 'user':
                $sql = "SELECT `usrid`, `user`, MIN(`date`) AS `dmin`, MAX(`date`) AS dmax, SUM(1) AS `nb` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY `usrid`\n"
                    // . " ORDER BY nb ASC\n"
                    . " WITH ROLLUP";
                break;
            case 'nav':
            case 'nav,version':
            case 'os':
            case 'os,nav':
            case 'os,nav,version':
            case 'res':
                $group = implode(
                    ',',
                    array_map(function($g) {return '`'.$g.'`';}, explode(',', $group))
                );
                $sql = "SELECT ".$group.", SUM(1) AS `nb` FROM `log`\n"
                    . " WHERE {{GlobalFilter}}\n"
                    . " GROUP BY ".$group."\n"
                    // . " ORDER BY nb ASC\n"
                    . " WITH ROLLUP"
                ;
                break;
            default:
                throw new InvalidArgumentException('invalid "group" argument');
                break;
        }

        $sql = str_replace(
            '{{GlobalFilter}}',
            "`site` =  :site AND !ISNULL(`usrid`) AND `date` >= :dmin AND `date` <= :dmax",
            $sql
        );
        $parms = array_merge(
            $parms,
            [   ':site' => $this->app['conf']->get(['main', 'key']),
                ':dmin' => $request->get('dmin'),
                ':dmax' => $request->get('dmax')
            ]
        );

        return $this->playSql($sbasId, $sql, $parms);
    }

    private function playSql($sbasId, $sql, $parms)
    {
        $stmt = $this->findDbConnectionOr404($sbasId)->prepare($sql);
        $stmt->execute($parms);
        $ret = $stmt->fetchAll();
        $stmt->closeCursor();

        return $ret;
    }

    /**
     * @param int $sbasId
     * @return Connection
     */
    protected function findDbConnectionOr404($sbasId)
    {
        $db = $this->findDataboxById($sbasId);
        if(!$db) {
            throw new NotFoundHttpException('Order not found');
        }

        return $db->get_connection();
    }

}
