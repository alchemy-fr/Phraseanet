<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class LoginTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Root.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Login::sendConfirmMail
     */
    public function testGetConfirMail()
    {
        $this->markTestIncomplete();
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Login::registerConfirm
     */
    public function testRegisterConfirmMail()
    {
        $this->markTestIncomplete();
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Login::renewPassword
     */
    public function testRenewPassword()
    {
        $this->markTestIncomplete();
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Login::displayForgotPasswordForm
     */
    public function testGetForgotPassword()
    {
        $this->markTestSkipped('Update rewrite rules');

        $this->client->request('GET', '/login/forgot-password/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Login::displayRegisterForm
     */
    public function testGetRegister()
    {
        $this->markTestSkipped('Update rewrite rules');
        
        $this->client->request('GET', '/login/register/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

        /**
     * @covers \Alchemy\Phrasea\Controller\Root/Login::logout
     */
    public function testGetLogout()
    {
        $this->markTestIncomplete();
    }


}
