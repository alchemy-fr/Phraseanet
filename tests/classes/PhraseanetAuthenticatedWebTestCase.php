<?php

use Doctrine\DBAL\DBALException;
use Silex\Application;
use Symfony\Component\DomCrawler\Crawler;

abstract class PhraseanetAuthenticatedWebTestCase extends \PhraseanetAuthenticatedTestCase
{
    private static $createdDataboxes = [];

    /**
     * @param bool $bool
     * @return PHPUnit_Framework_MockObject_MockObject The stubbedACL
     */
    public function setAdmin($bool)
    {
        $stubbedACL = $this->stubACL();

        $stubbedACL->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue($bool));

        $stubbedACL->expects($this->any())
            ->method('give_access_to_sbas')
            ->will($this->returnSelf());

        $stubbedACL->expects($this->any())
            ->method('update_rights_to_sbas')
            ->will($this->returnSelf());

        $stubbedACL->expects($this->any())
            ->method('update_rights_to_bas')
            ->will($this->returnSelf());

        $stubbedACL->expects($this->any())
            ->method('has_right_on_base')
            ->will($this->returnValue($bool));

        $stubbedACL->expects($this->any())
            ->method('has_right_on_sbas')
            ->will($this->returnValue($bool));

        $stubbedACL->expects($this->any())
            ->method('has_access_to_sbas')
            ->will($this->returnValue($bool));

        $stubbedACL->expects($this->any())
            ->method('has_access_to_base')
            ->will($this->returnValue($bool));

        $stubbedACL->expects($this->any())
            ->method('has_right')
            ->will($this->returnValue($bool));

        $stubbedACL->expects($this->any())
            ->method('has_access_to_module')
            ->will($this->returnValue($bool));

        $stubbedACL->expects($this->any())
            ->method('get_granted_base')
            ->will($this->returnValue([self::$DI['collection']]));

        $stubbedACL->expects($this->any())
            ->method('get_granted_sbas')
            ->will($this->returnValue([self::$DI['collection']->get_databox()]));

        return $stubbedACL;
    }

    public function createDatabox()
    {
        $this->createDatabase();

        $app = self::$DI['app'];
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

        $rights = [
            'bas_manage'        => '1'
            , 'bas_modify_struct' => '1'
            , 'bas_modif_th'      => '1'
            , 'bas_chupub'        => '1'
        ];

        $app->getAclForUser($app->getAuthenticatedUser())->update_rights_to_sbas($databox->get_sbas_id(), $rights);

        $databox->registerAdmin($app->getAuthenticatedUser());

        return $databox;
    }

    public static function dropDatabase()
    {
        $stmt = self::$DI['app']['phraseanet.appbox']
            ->get_connection()
            ->prepare('DROP DATABASE IF EXISTS `unit_test_db`');
        $stmt->execute();
        $stmt->closeCursor();
        $stmt = self::$DI['app']['phraseanet.appbox']
            ->get_connection()
            ->prepare('DELETE FROM sbas WHERE dbname = "unit_test_db"');
        $stmt->execute();
        $stmt->closeCursor();
    }

    protected function createDatabase()
    {
        self::dropDatabase();

        $stmt = self::$DI['app']['phraseanet.appbox']
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
}
