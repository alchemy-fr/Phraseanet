<?php

class http_requestTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var http_request
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new http_request();
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf('http_request', http_request::getInstance());
    }

    public function testIs_ajax()
    {
        $this->assertFalse($this->object->is_ajax());
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
        $this->assertTrue($this->object->is_ajax());
    }

    public function testComes_from_flash()
    {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;
        $this->assertFalse($this->object->comes_from_flash());
        $_SERVER['HTTP_USER_AGENT'] = 'Shockwave Flash';
        $this->assertTrue($this->object->comes_from_flash());
        $_SERVER['HTTP_USER_AGENT'] = 'Shockwave Flash Player';
        $this->assertTrue($this->object->comes_from_flash());
        $_SERVER['HTTP_USER_AGENT'] = 'Adobe Flash Player';
        $this->assertTrue($this->object->comes_from_flash());
        $_SERVER['HTTP_USER_AGENT'] = 'Adobe Flash Player 10';
        $this->assertTrue($this->object->comes_from_flash());
        $_SERVER['HTTP_USER_AGENT'] = 'Flash';
        $this->assertTrue($this->object->comes_from_flash());
        $_SERVER['HTTP_USER_AGENT'] = 'Flash Player';
        $this->assertTrue($this->object->comes_from_flash());
        $_SERVER['HTTP_USER_AGENT'] = 'Flash ';
        $this->assertTrue($this->object->comes_from_flash());
        $_SERVER['HTTP_USER_AGENT'] = 'Flashs ';
        $this->assertFalse($this->object->comes_from_flash());
        $_SERVER['HTTP_USER_AGENT'] = $user_agent;
        $this->assertFalse($this->object->comes_from_flash());
    }

    public function testGet_code()
    {
        $this->assertNull($this->object->get_code());
        $_SERVER['REDIRECT_STATUS'] = 301;
        $this->assertEquals(301, $this->object->get_code());
        $this->object->set_code(580);
        $this->assertEquals(580, $this->object->get_code());
        $this->object->set_code('a');
        $this->assertEquals(0, $this->object->get_code());
        $this->object->set_code('a');
        $this->assertEquals(0, $this->object->get_code());
    }

    public function testSet_code()
    {
        $this->object->set_code(302);
        $this->assertEquals(302, $this->object->get_code());
    }

    public function testGet_parms()
    {
        $_GET = array('lili' => '25', 'popo' => array('tip', 'top'));
        $_POST = array('Plili' => '25', 'Gpopo' => array('mtip', 'btop'));

        $parm = $this->object->get_parms('lili', 'Plili', 'popo', 'Gpopo', 'notexists');

        $this->assertEquals($_GET['lili'], $parm['lili']);
        $this->assertEquals($_POST['Plili'], $parm['Plili']);
        $this->assertEquals($_GET['popo'], $parm['popo']);
        $this->assertEquals($_POST['Gpopo'], $parm['Gpopo']);
        $this->assertNull($parm['notexists']);

        $parm = $this->object->get_parms(
            array(
                'lili'      => http_request::SANITIZE_NUMBER_INT
                , 'Plili'
                , 'popo'
                , 'notexists' => http_request::SANITIZE_STRING
            )
        );

        $this->assertEquals((int) $_GET['lili'], $parm['lili']);
        $this->assertTrue(is_int($parm['lili']));
        $this->assertEquals($_POST['Plili'], $parm['Plili']);
        $this->assertEquals($_GET['popo'], $parm['popo']);
        $this->assertEquals('', $parm['notexists']);

        $_GET = $_POST = array();
    }

    public function testGet_parmsArraToString()
    {
        $_POST = array('Plili' => '25', 'Gpopo' => array('mtip', 'btop'));

        try {
            $this->object->get_parms(array('Gpopo' => http_request::SANITIZE_STRING));

            if (version_compare(PHP_VERSION, '5.4', '>=')) {
                $this->fail('Should raise an error notice');
            }
        } catch (\PHPUnit_Framework_Error_Notice $e) {

        }
        $_GET = $_POST = array();
    }

    public function testGet_parms_from_serialized_datas()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testHas_post_datas()
    {
        $this->assertFalse($this->object->has_post_datas());
        $_POST = array('TOPPy' => null);
        $this->assertTrue($this->object->has_post_datas());
    }

    public function testGet_post_datas()
    {
        $post = $_POST = array('Plili' => '25', 'Gpopo' => array('mtip', 'btop'));
        $this->assertEquals($post, $this->object->get_post_datas());
    }

    public function testHas_get_datas()
    {
        $this->assertFalse($this->object->has_get_datas());
        $_GET = array('TOPPy' => null);
        $this->assertTrue($this->object->has_get_datas());
    }

    public function testHas_datas()
    {
        $_POST = $_GET = array();
        $this->assertFalse($this->object->has_datas());
        $_POST = array('malal' => true);
        $this->assertTrue($this->object->has_datas());
        $_GET = array('malal' => true);
        $_POST = array();
        $this->assertTrue($this->object->has_datas());
        $_GET = array('malal' => true);
        $_POST = array('malal' => true);
        $this->assertTrue($this->object->has_datas());
        $_POST = $_GET = array();
        $this->assertFalse($this->object->has_datas());
    }

    /**
     * @todo Implement testFilter().
     */
    public function testFilter()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testIs_command_line()
    {
        $this->assertTrue($this->object->is_command_line());
    }
}
