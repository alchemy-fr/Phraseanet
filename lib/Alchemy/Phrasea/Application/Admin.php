<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Controller\Admin\Collection;
use Alchemy\Phrasea\Controller\Admin\ConnectedUsers;
use Alchemy\Phrasea\Controller\Admin\Dashboard;
use Alchemy\Phrasea\Controller\Admin\Databox;
use Alchemy\Phrasea\Controller\Admin\Databoxes;
use Alchemy\Phrasea\Controller\Admin\Description;
use Alchemy\Phrasea\Controller\Admin\Fields;
use Alchemy\Phrasea\Controller\Admin\Publications;
use Alchemy\Phrasea\Controller\Admin\Root;
use Alchemy\Phrasea\Controller\Admin\Setup;
use Alchemy\Phrasea\Controller\Admin\Sphinx;
use Alchemy\Phrasea\Controller\Admin\Subdefs;
use Alchemy\Phrasea\Controller\Admin\Users;
use Alchemy\Phrasea\Controller\Admin\TaskManager;
use Alchemy\Phrasea\Controller\Utils\ConnectionTest;
use Alchemy\Phrasea\Controller\Utils\PathFileTest;
use Silex\ControllerProviderInterface;
use Silex\Application As SilexApplication;

class Admin implements ControllerProviderInterface
{

    public function connect(SilexApplication $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->mount('/', new Root());
        $controllers->mount('/dashboard', new Dashboard());
        $controllers->mount('/collection', new Collection());
        $controllers->mount('/databox', new Databox());
        $controllers->mount('/databoxes', new Databoxes());
        $controllers->mount('/setup', new Setup());
        $controllers->mount('/sphinx', new Sphinx());
        $controllers->mount('/connected-users', new ConnectedUsers());
        $controllers->mount('/publications', new Publications());
        $controllers->mount('/users', new Users());
        $controllers->mount('/fields', new Fields());
        $controllers->mount('/subdefs', new Subdefs());
        $controllers->mount('/description', new Description());
        $controllers->mount('/tests/connection', new ConnectionTest());
        $controllers->mount('/tests/pathurl', new PathFileTest());

        return $controllers;
    }
}
