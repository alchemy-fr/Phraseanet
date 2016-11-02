<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\ElasticsearchRecord;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Alchemy\Phrasea\SearchEngine\SearchEngineResult;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\DBALException;
use Prophecy\Argument;
use Symfony\Component\DomCrawler\Crawler;

abstract class PhraseanetAuthenticatedWebTestCase extends \PhraseanetAuthenticatedTestCase
{
    private static $createdDataboxes = [];

    /**
     * @param bool  $bool
     * @param array $stubs List of Closure to call indexed by method names, null to avoid calls
     * @return PHPUnit_Framework_MockObject_MockObject The stubbedACL
     */
    public function setAdmin($bool, array $stubs = [])
    {
        $stubbedACL = $this->stubACL();
        $stubs = array_filter(array_replace($this->getDefaultStubs(), $stubs));

        foreach ($stubs as $method => $stub) {
            call_user_func($stub, $stubbedACL, $method, $bool);
        }

        return $stubbedACL;
    }

    protected function getDefaultStubs()
    {
        static $stubs;

        if (is_array($stubs)) {
            return $stubs;
        }

        $returnBool = function (PHPUnit_Framework_MockObject_MockObject $acl, $method, $is_admin) {
            $acl->expects($this->any())
                ->method($method)
                ->will($this->returnValue($is_admin));
        };

        $returnSelf = function (PHPUnit_Framework_MockObject_MockObject $acl, $method) {
            $acl->expects($this->any())
                ->method($method)
                ->will($this->returnSelf());
        };

        $stubGrantedBase = function (PHPUnit_Framework_MockObject_MockObject $acl, $method) {
            $acl->expects($this->any())
                ->method($method)
                ->will($this->returnValue([self::$DI['collection']]));
        };
        $stubGrantedSBase = function (PHPUnit_Framework_MockObject_MockObject $acl, $method) {
            $acl->expects($this->any())
                ->method($method)
                ->will($this->returnValue([self::$DI['collection']->get_databox()]));
        };

        $stubs = [
            'is_admin' => $returnBool,
            'give_access_to_sbas' => $returnSelf,
            'update_rights_to_sbas' => $returnSelf,
            'update_rights_to_base' => $returnSelf,
            'has_right_on_base' => $returnBool,
            'has_right_on_sbas' => $returnBool,
            'has_access_to_sbas' => $returnBool,
            'has_access_to_base' => $returnBool,
            'has_right' => $returnBool,
            'has_access_to_module' => $returnBool,
            'get_granted_base' => $stubGrantedBase,
            'get_granted_sbas' => $stubGrantedSBase,
        ];

        return $stubs;
    }

    public function createDatabox()
    {
        $this->createDatabase();

        $app = $this->getApplication();
        $info = $app['phraseanet.configuration']['main']['database'];

        try {
            $conn = $app['connection.pool.manager']->get([
                'host'     => $info['host'],
                'port'     => $info['port'],
                'user'     => $info['user'],
                'password' => $info['password'],
                'dbname'   => 'unit_test_db',
            ]);
            $conn->connect();
        } catch (DBALException $e) {
            $this->markTestSkipped('Could not reach DB');
        }

        $databox = \databox::create(
            $app,
            $conn,
            new \SplFileInfo($app['root.path'] . '/lib/conf.d/data_templates/fr-simple.xml')
        );

        self::$createdDataboxes[] = $databox;

        $app->getAclForUser($app->getAuthenticatedUser())->update_rights_to_sbas(
            $databox->get_sbas_id(),
            [
                \ACL::BAS_MANAGE        => true,
                \ACL::BAS_MODIFY_STRUCT => true,
                \ACL::BAS_MODIF_TH      => true,
                \ACL::BAS_CHUPUB        => true
            ]
        );

        $databox->registerAdmin($app->getAuthenticatedUser());

        return $databox;
    }

    public function dropDatabase()
    {
        $app = $this->getApplication();
        $connection = $app->getApplicationBox()->get_connection();
        $stmt = $connection->prepare('DROP DATABASE IF EXISTS `unit_test_db`');
        $stmt->execute();
        $stmt->closeCursor();
        $stmt = $connection->prepare('DELETE FROM sbas WHERE dbname = "unit_test_db"');
        $stmt->execute();
        $stmt->closeCursor();
    }

    protected function createDatabase()
    {
        $this->dropDatabase();

        $app = $this->getApplication();
        $stmt = $app
            ->getApplicationBox()
            ->get_connection()
            ->prepare('CREATE DATABASE `unit_test_db`
              CHARACTER SET utf8 COLLATE utf8_unicode_ci');
        $stmt->execute();
        $stmt->closeCursor();
    }

    public function provideFlashMessages()
    {
        return [
            ['warning', 'Be careful !'],
            ['error', 'An error occured'],
            ['info', 'You need to do something more'],
            ['success', "Success operation !"],
        ];
    }

    protected function assertFormOrFlashError(Crawler $crawler, $quantity)
    {
        $total = $crawler->filter('.field-error')->count();
        $total += $crawler->filter('.alert')->count();

        $this->assertEquals($quantity, $total);
    }

    protected function assertFormError(Crawler $crawler, $quantity)
    {
        $this->assertEquals($quantity, $crawler->filter('.field-error')->count());
    }

    protected function assertFlashMessage(Crawler $crawler, $flashType, $quantity, $message = null, $offset = 0)
    {
        if (!preg_match('/[a-zA-Z]+/', $flashType)) {
            $this->fail(sprintf('FlashType must be in the form of [a-zA-Z]+, %s given', $flashType));
        }

        $this->assertEquals($quantity, $crawler->filter('.alert.alert-'.$flashType)->count());

        if (null !== $message) {
            $this->assertEquals($message, $crawler->filter('.alert.alert-'.$flashType.' .alert-block-content')->eq($offset)->text());
        }
    }

    protected function assertFlashMessagePopulated(Application $app, $flashType, $quantity)
    {
        if (!preg_match('/[a-zA-Z]+/', $flashType)) {
            $this->fail(sprintf('FlashType must be in the form of [a-zA-Z]+, %s given', $flashType));
        }

        $this->assertEquals($quantity, count($app['session']->getFlashBag()->get($flashType)));
    }

    /**
     * @param \record_adapter $record
     * @return \Alchemy\Phrasea\Application
     */
    protected function mockElasticsearchResult(\record_adapter $record)
    {
        $app = $this->getApplication();

        $elasticsearchRecord = new ElasticsearchRecord();
        $elasticsearchRecord->setDataboxId($record->getDataboxId());
        $elasticsearchRecord->setRecordId($record->getRecordId());

        $result = new SearchEngineResult(
            new SearchEngineOptions(),
            new ArrayCollection([$elasticsearchRecord]), // Records
            '', // Query as user typed
            '{}', // Query as engine parsed
            0, // Duration
            0, // offsetStart
            1, // available
            1, // total
            0, // warning
            0, // error
            new ArrayCollection(), // suggestions
            null, // propositions
            null // indexes
        );

        $searchEngine = $this->prophesize(SearchEngineInterface::class);
        $searchEngine->query('', Argument::any())
            ->willReturn($result);

        $app['search_engine'] = $searchEngine->reveal();
        return $app;
    }
}
