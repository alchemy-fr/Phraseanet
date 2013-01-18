<?php

class Setup_UpgradeTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var Setup_Upgrade
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = new Setup_Upgrade(self::$DI['app']);
    }

    public function tearDown()
    {
        unset($this->object);
        parent::tearDown();
    }

    public function test__destruct()
    {
        $this->assertFileExists(Setup_Upgrade::get_lock_file());
        unset($this->object);
        $this->assertFileNotExists(Setup_Upgrade::get_lock_file());
    }

    public function testAdd_steps()
    {
        $this->check_percentage(1, 0, 0);
        $this->object->add_steps(1);
        $this->check_percentage(0, 1, 0);
        $this->object->add_steps('lsdf');
        $this->check_percentage(0, 1, 0);
        $this->object->add_steps(20);
        $this->check_percentage(0, 21, 0);
        $this->object->add_steps(-5);
        $this->check_percentage(0, 16, 0);
    }

    protected function check_percentage($percent, $total, $complete)
    {
        $datas = $this->object->get_status();
        $this->assertArrayHasKey('completed_steps', $datas);
        $this->assertArrayHasKey('total_steps', $datas);
        $this->assertArrayHasKey('percentage', $datas);
        $this->assertArrayHasKey('last_update', $datas);
        $this->assertDateAtom($datas['last_update']);
        $this->assertEquals($percent, $datas['percentage']);
        $this->assertEquals($total, $datas['total_steps']);
        $this->assertEquals($complete, $datas['completed_steps']);
    }

    public function testAdd_steps_complete()
    {
        $this->check_percentage(1, 0, 0);
        $this->object->add_steps(1)->add_steps_complete(1);
        $this->check_percentage(1, 1, 1);
        $this->object->add_steps(20)->add_steps_complete(20);
        $this->check_percentage(1, 21, 21);
        $this->object->add_steps(20);
        $this->check_percentage(round(21 / 41, 2), 41, 21);
        $this->object->add_steps_complete(40);
        $this->check_percentage(1, 41, 61);
    }

    public function testSet_current_message()
    {
        $message = 'ZOubid  èèè\\';
        $this->object->set_current_message($message);

        $datas = $this->object->get_status();
        $this->assertArrayHasKey('message', $datas);
        $this->assertEquals($message, $datas['message']);
    }

    public function testGet_status()
    {
        $datas = $this->object->get_status();
        $this->assertTrue(is_array($datas));
        $this->assertArrayHasKey('active', $datas);
        $this->assertArrayHasKey('percentage', $datas);
        $this->assertArrayHasKey('total_steps', $datas);
        $this->assertArrayHasKey('completed_steps', $datas);
        $this->assertArrayHasKey('message', $datas);
        $this->assertArrayHasKey('last_update', $datas);
        $this->assertDateAtom($datas['last_update']);
    }
}
