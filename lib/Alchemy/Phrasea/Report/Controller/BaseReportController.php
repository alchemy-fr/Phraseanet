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
use Alchemy\Phrasea\Application\Helper\JsonBodyAware;
use Alchemy\Phrasea\Controller\Controller;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


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
