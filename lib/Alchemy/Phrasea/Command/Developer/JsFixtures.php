<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\User;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Client;

class JsFixtures extends Command
{
    public function __construct()
    {
        parent::__construct('phraseanet:generate-js-fixtures');

        $this->setDescription('Generate JS fixtures');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $dbRefPath = __DIR__ . '/../../../../../tests/db-ref.sqlite';
        if (!file_exists($dbRefPath)) {
            throw new RuntimeException('You must generate sqlite db first, run "bin/console phraseanet:regenerate-sqlite" command.');
        }

        copy($dbRefPath, '/tmp/db.sqlite');

        $sbasId = current($this->container['phraseanet.appbox']->get_databoxes())->get_sbas_id();
        $this->writeResponse($output, 'GET', '/login/', '/home/login/index.html');
        $this->writeResponse($output, 'GET', '/admin/fields/'.$sbasId , '/admin/fields/index.html', true);
        $this->writeResponse($output, 'GET', '/admin/task-manager/tasks', '/admin/task-manager/index.html', true);
        $this->writeResponse($output, 'GET', '/admin/', '/admin/main/left-panel.html', true);
        $this->writeResponse($output, 'GET', '/admin/databoxes/', '/admin/main/right-panel.html', true);

        $this->copy($output, [
            ['source' => 'login/common/templates.html.twig', 'target' => 'home/login/templates.html'],
            ['source' => 'admin/fields/templates.html.twig', 'target' => 'admin/fields/templates.html'],
            ['source' => 'admin/task-manager/templates.html.twig', 'target' => 'admin/task-manager/templates.html'],
        ]);

        return 0;
    }

    private function deleteUser(User $user)
    {
        $this->container['manipulator.user']->delete($user);
    }

    private function copy(OutputInterface $output, $data)
    {
        foreach ($data as $paths) {
            $output->writeln(sprintf("Generating %s", $this->container['root.path'] . '/www/scripts/tests/fixtures/'.$paths['target']));
            $this->container['filesystem']->copy(
                $this->container['root.path'] . '/templates/web/'.$paths['source'],
                $this->container['root.path'] . '/www/scripts/tests/fixtures/'.$paths['target']
            );
        }
    }

    private function removeScriptTags($html)
    {
        return preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
    }

    private function removeHeadTag($html)
    {
        return preg_replace('#<head>(.*?)</head>#is', '', $html);
    }

    private function createUser(Application $app)
    {
        $user = $app['manipulator.user']->createUser(uniqid('fixturejs'), uniqid('fixturejs'), uniqid('fixturejs') . '@js.js', true);

        $app['acl']->get($user)->set_admin(true);
        $app['manipulator.acl']->resetAdminRights($user);

        return $user;
    }

    private function loginUser(Application $app, User $user)
    {
        $app['authentication']->openAccount($user);
    }

    private function logoutUser(Application $app)
    {
        $app['authentication']->closeAccount();
    }

    private function writeResponse(OutputInterface $output, $method, $path, $to, $authenticateUser = false)
    {
        $environment = Application::ENV_TEST;
        $app = require __DIR__ . '/../../Application/Root.php';

        $user = $this->createUser($app);

        // force load of non cached template
        $app['twig']->enableAutoReload();
        $client = new Client($app);
        $fixturePath =  'www/scripts/tests/fixtures';
        $target = sprintf('%s/%s/%s', $app['root.path'],$fixturePath, $to);
        $output->writeln(sprintf("Generating %s", $target));

        if ($authenticateUser) {
            $this->loginUser($app, $user);
        }
        $client->request($method, $path);
        $response = $client->getResponse();
        if ($authenticateUser) {
            $this->logoutUser($app);
        }
        if (false === $response->isOk()) {
            $this->deleteUser($user);
            throw new RuntimeException(sprintf('Request %s %s returns %d code error', $method, $path, $response->getStatusCode()));
        }

        $this->container['filesystem']->mkdir(str_replace(basename($target), '', $target));
        $this->container['filesystem']->dumpFile($target, $this->removeHeadTag($this->removeScriptTags($response->getContent())));
        $this->deleteUser($user);
    }
}
