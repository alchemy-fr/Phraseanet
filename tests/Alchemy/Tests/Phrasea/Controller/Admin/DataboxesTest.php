<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\Database::class, 'databaseMount')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Alchemy\Phrasea\Controller\Admin\Database::class, 'createDatabase')]
#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
#[\PHPUnit\Framework\Attributes\Group('authenticated')]
#[\PHPUnit\Framework\Attributes\Group('web')]
class DataboxesTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    #[\PHPUnit\Framework\Attributes\DataProvider('msgProvider')]
    public function testGetSlash($type, $errorMsgId)
    {
        self::$DI['client']->request('GET', '/admin/databoxes/', [
            $type => $errorMsgId
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public static function msgProvider()
    {
        return [
            ['error', 'already-started'],
            ['error', 'scheduler-started'],
            ['error', 'unknow'],
            ['error', 'bad-email'],
            ['error', 'special-chars'],
            ['error', 'base-failed'],
            ['error', 'database-failed'],
            ['error', 'no-empty'],
            ['success', 'restart'],
            ['success', 'mount-ok'],
            ['success', 'database-ok'],
        ];
    }

    public function testGetSlashUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databoxes/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    public function testMountBase()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();
        $base->unmount_databox();

        self::$DI['client']->request('POST', '/admin/databoxes/mount/', [
            'new_dbname' => 'unit_test_db'
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $uriRedirect = $response->headers->get('location');

        $this->assertTrue(!!strrpos($uriRedirect, 'success=1'));
        $explode = explode('/', $uriRedirect);
        $databoxId = $explode[3];

        try {
            $databox = self::$DI['app']->findDataboxById($databoxId);
            $databox->unmount_databox();
            $databox->delete();
        } catch (NotFoundHttpException $e) {
            $this->fail('databox not mounted');
        }

        unset($databox);
    }

    public function testCreateDatabaseEmpty()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/databoxes/', [
            'new_dbname' => ''
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/admin/databoxes/?success=0&error=no-empty', $response->headers->get('location'));
    }

    public function testCreateDatabaseSpecialChar()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/databoxes/', [
            'new_dbname' => 'ééààèè'
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/admin/databoxes/?success=0&error=special-chars', $response->headers->get('location'));
    }

    public function testCreateDatabase()
    {
        $this->setAdmin(true);

        $this->createDatabase();

        self::$DI['client']->request('POST', '/admin/databoxes/', [
            'new_dbname'        => 'unit_test_db',
            'new_data_template' => 'fr-simple',
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $uriRedirect = $response->headers->get('location');
        $this->assertTrue(!!strrpos($uriRedirect, 'success=1'));
        $explode = explode('/', $uriRedirect);
        $databoxId = $explode[3];
        $databox = self::$DI['app']->findDataboxById($databoxId);
        $databox->unmount_databox();
        $databox->delete();

        unset($stmt, $databox);
    }
}
