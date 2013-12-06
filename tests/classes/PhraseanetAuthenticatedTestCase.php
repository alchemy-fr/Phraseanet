<?php

abstract class PhraseanetAuthenticatedTestCase extends \PhraseanetTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->authenticate(self::$DI['app']);
    }

    public function tearDown()
    {
        $this->logout(self::$DI['app']);
        parent::tearDown();
    }
}
