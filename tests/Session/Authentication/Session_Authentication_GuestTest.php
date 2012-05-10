<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class Session_Authentication_GuestTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var Session_Authentication_Guest
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new Session_Authentication_Guest(appbox::get_instance(\bootstrap::getCore()));
    }

    public function testSignOn()
    {
        $user = $this->object->signOn();
        $this->assertInstanceOf('User_Adapter', $user);
        $this->assertTrue($user->is_guest());
    }
}
