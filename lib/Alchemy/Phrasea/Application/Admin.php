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

use Alchemy\Phrasea\Application as PhraseaApplication;
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
use Alchemy\Phrasea\Controller\Admin\Tasks;
use Alchemy\Phrasea\Controller\Admin\Task;
use Alchemy\Phrasea\Controller\Admin\TaskManager;
use Alchemy\Phrasea\Controller\Admin\Scheduler;
use Alchemy\Phrasea\Controller\Utils\ConnectionTest;
use Alchemy\Phrasea\Controller\Utils\PathFileTest;

return call_user_func(
        function() {
            $app = new PhraseaApplication();

            $app->mount('/', new Root());
            $app->mount('/dashboard', new Dashboard());
            $app->mount('/collection', new Collection());
            $app->mount('/databox', new Databox());
            $app->mount('/databoxes', new Databoxes());
            $app->mount('/setup', new Setup());
            $app->mount('/sphinx', new Sphinx());
            $app->mount('/connected-users', new ConnectedUsers());

            $app->mount('/task-manager', new TaskManager());

//            $app->mount('/tasks', new Tasks());
//            $app->mount('/task', new Task());
//            $app->mount('/scheduler', new Scheduler());

            $app->mount('/publications', new Publications());
            $app->mount('/users', new Users());
            $app->mount('/fields', new Fields());
            $app->mount('/subdefs', new Subdefs());
            $app->mount('/description', new Description());
            $app->mount('/tests/connection', new ConnectionTest());
            $app->mount('/tests/pathurl', new PathFileTest());

            return $app;
        }
);
