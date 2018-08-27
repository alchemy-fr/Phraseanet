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


class BaseReportController extends Controller
{
    use JsonBodyAware;

    private $acl;

    /**
     * @param Application $app
     * @param \ACL $acl
     */
    public function __construct(Application $app, \ACL $acl)
    {
        parent::__construct($app);
        $this->acl = $acl;
        //$id = $this->getAuthenticator()->getUser()->getId();
        $app->getAuthenticatedUser();
        // $this->getAuthenticatedUser()->isAdmin();
    }

}
