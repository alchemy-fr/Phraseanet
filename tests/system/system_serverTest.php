<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class system_serverTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var system_server
     */
    protected $objects = array();

    public function setUp()
    {
        parent::setUp();
        $_SERVER['SERVER_SOFTWARE'] = 'apache';
        $this->objects['apache'] = new system_server;
        $_SERVER['SERVER_SOFTWARE'] = 'nginx';
        $this->objects['nginx'] = new system_server;
        $_SERVER['SERVER_SOFTWARE'] = 'lighttpd';
        $this->objects['lighttpd'] = new system_server;
    }

    public function testIs_nginx()
    {
        $this->assertFalse($this->objects['apache']->is_nginx());
        $this->assertTrue($this->objects['nginx']->is_nginx());
        $this->assertFalse($this->objects['lighttpd']->is_nginx());
    }

    public function testIs_lighttpd()
    {
        $this->assertFalse($this->objects['apache']->is_lighttpd());
        $this->assertFalse($this->objects['nginx']->is_lighttpd());
        $this->assertTrue($this->objects['lighttpd']->is_lighttpd());
    }

    public function testIs_apache()
    {
        $this->assertTrue($this->objects['apache']->is_apache());
        $this->assertFalse($this->objects['nginx']->is_apache());
        $this->assertFalse($this->objects['lighttpd']->is_apache());
    }

}
