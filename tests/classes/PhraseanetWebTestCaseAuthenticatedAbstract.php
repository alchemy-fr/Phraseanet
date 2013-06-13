<?php

use Silex\Application;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\DomCrawler\Crawler;

abstract class PhraseanetWebTestCaseAuthenticatedAbstract extends PhraseanetPHPUnitAuthenticatedAbstract
{
    protected $StubbedACL;
    protected static $createdDataboxes = array();

    public function setUp()
    {
        parent::setUp();

        $this->StubbedACL = $this->getMockBuilder('\ACL')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function setAdmin($bool)
    {
        $stubAuthenticatedUser = $this->getMockBuilder('\User_Adapter')//, array('is_admin', 'ACL'), array(self::$DI['app']['authentication']->getUser()->get_id(), self::$DI['app']))
            ->setMethods(array('ACL', 'get_id'))
            ->disableOriginalConstructor()
            ->getMock();

        $this->StubbedACL->expects($this->any())
            ->method('is_admin')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('give_access_to_sbas')
            ->will($this->returnValue($this->StubbedACL));

        $this->StubbedACL->expects($this->any())
            ->method('update_rights_to_sbas')
            ->will($this->returnValue($this->StubbedACL));

        $this->StubbedACL->expects($this->any())
            ->method('update_rights_to_bas')
            ->will($this->returnValue($this->StubbedACL));

        $this->StubbedACL->expects($this->any())
            ->method('has_right_on_base')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('has_right_on_sbas')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('has_access_to_sbas')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('has_access_to_base')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('has_right')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('has_access_to_module')
            ->will($this->returnValue($bool));

        $this->StubbedACL->expects($this->any())
            ->method('get_granted_base')
            ->will($this->returnValue(array(self::$DI['collection'])));

        $this->StubbedACL->expects($this->any())
            ->method('get_granted_sbas')
            ->will($this->returnValue(array(self::$DI['collection']->get_databox())));

        $stubAuthenticatedUser->expects($this->any())
            ->method('ACL')
            ->will($this->returnValue($this->StubbedACL));

        $stubAuthenticatedUser->expects($this->any())
            ->method('get_id')
            ->will($this->returnValue(self::$DI['user']->get_id()));

        self::$DI['app']['authentication']->setUser($stubAuthenticatedUser);

        self::$DI['client'] = self::$DI->share(function($DI) {
                return new Client($DI['app'], array());
            });
    }

    public function createDatabox()
    {
        $this->createDatabase();

        $connexion = self::$DI['app']['phraseanet.configuration']['main']['database'];

        try {
            $conn = new \connection_pdo(
                'databox_creation',
                $connexion['host'],
                $connexion['port'],
                $connexion['user'],
                $connexion['password'],
                'unit_test_db',
                array(),
                false
            );
        } catch (\PDOException $e) {

            $this->markTestSkipped('Could not reach DB');
        }

        $databox = \databox::create(
                self::$DI['app'], $conn, new \SplFileInfo(self::$DI['app']['phraseanet.registry']->get('GV_RootPath') . 'lib/conf.d/data_templates/fr-simple.xml'), self::$DI['app']['phraseanet.registry']
        );

        self::$createdDataboxes[] = $databox;

        $rights = array(
            'bas_manage'        => '1'
            , 'bas_modify_struct' => '1'
            , 'bas_modif_th'      => '1'
            , 'bas_chupub'        => '1'
        );

        self::$DI['app']['authentication']->getUser()->ACL()->update_rights_to_sbas($databox->get_sbas_id(), $rights);

        $databox->registerAdmin(self::$DI['app']['authentication']->getUser());

        return $databox;
    }

    public static function dropDatabase()
    {
        $stmt = self::$DI['app']['phraseanet.appbox']
            ->get_connection()
            ->prepare('DROP DATABASE IF EXISTS `unit_test_db`');
        $stmt->execute();
        $stmt = self::$DI['app']['phraseanet.appbox']
            ->get_connection()
            ->prepare('DELETE FROM sbas WHERE dbname = "unit_test_db"');
        $stmt->execute();
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
        return array(
            array('warning', 'Be careful !'),
            array('error', 'An error occured'),
            array('info', 'You need to do something more'),
            array('success', "Success operation !"),
        );
    }

    protected function assertFormOrFlashError(Crawler $crawler, $quantity)
    {
        $total = $crawler->filter('form div:not(div[ng-show]) > div.popover.field-error')->count();
        $total += $crawler->filter('.alert')->count();

        $this->assertEquals($quantity, $total);
    }

    protected function assertFormError(Crawler $crawler, $quantity)
    {
        $this->assertEquals($quantity, $crawler->filter('form div:not(div[ng-show]) > div.popover.field-error')->count());
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
