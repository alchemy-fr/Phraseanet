<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class AccountTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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
     * @covers \Alchemy\Phrasea\Controller\Root/Account::displayAccount
     */
    public function testGetAccount()
    {
        $this->client->request('GET', '/account/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Account::accountAccess
     */
    public function testGetAccountAccess()
    {
        $this->client->request('GET', '/account/access/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Account::resetEmail
     */
    public function testGetResetMail()
    {
        $this->client->request('GET', '/account/reset-email/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Account::accountSessionsAccess
     */
    public function testGetAccountSecuritySessions()
    {
        $this->client->request('GET', '/account/security/sessions/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Account::accountAuthorizedApps
     */
    public function testGetAccountSecurityApplications()
    {
        $this->client->request('GET', '/account/security/applications/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Account::resetPassword
     */
    public function testGetResetPassword()
    {
        $this->client->request('GET', '/account/reset-password/');

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Root/Account::renewPassword
     */
    public function testUpdateAccount()
    {
        $core = \bootstrap::getCore();
        $appbox = \appbox::get_instance($core);

        $bases = array();
        foreach ($appbox->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $bases[] = $collection->get_base_id();
            }
        }

        if(0 === count($bases)) {
            $this->markTestSkipped('No collections');
        }

        $this->client->request('POST', '/account/', array(
        'demand' => $bases,
        'form_gender' => 'M',
        'form_firstname' => 'gros',
        'form_lastname' => 'minet',
        'form_address' => 'rue du lac',
        'form_zip' => '75005',
        'form_phone' => '+33645787878',
        'form_fax' => '+33145787845',
        'form_function' => 'astronaute',
        'form_company' => 'NASA',
        'form_activity' => 'Space',
        'form_geonameid' => '',
        'form_addrFTP' => '',
        'form_loginFTP' => '',
        'form_pwdFTP' => '',
        'form_destFTP' => '',
        'form_prefixFTPfolder' => '',
        'form_defaultdataFTP' => array('document', 'preview', 'caption'),
        'mail_notifications' => '1'
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('minet', $core->getAUthenticatedUser()->get_lastname());
    }


}
