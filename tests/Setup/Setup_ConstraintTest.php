<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class Setup_ConstraintTest extends PhraseanetPHPUnitAbstract
{
    protected $object_non_blocker;
    protected $object_blocker;
    protected $message = "&é'(§è!çà dfljk sdq'";
    protected $name = "Un joli nom";

    public function setUp()
    {
        parent::setUp();
        $this->object_non_blocker = new Setup_Constraint($this->name, true, $this->message, false);
        $this->object_blocker = new Setup_Constraint($this->name, false, $this->message, true);
    }

    public function testGet_name()
    {
        $this->assertEquals($this->name, $this->object_blocker->get_name());
        $this->assertEquals($this->name, $this->object_non_blocker->get_name());
    }

    public function testIs_ok()
    {
        $this->assertFalse($this->object_blocker->is_ok());
        $this->assertTrue($this->object_non_blocker->is_ok());
    }

    public function testIs_blocker()
    {
        $this->assertTrue($this->object_blocker->is_blocker());
        $this->assertFalse($this->object_non_blocker->is_blocker());
    }

    public function testGet_message()
    {
        $this->assertEquals($this->message, $this->object_blocker->get_message());
        $this->assertEquals($this->message, $this->object_non_blocker->get_message());
    }
}
