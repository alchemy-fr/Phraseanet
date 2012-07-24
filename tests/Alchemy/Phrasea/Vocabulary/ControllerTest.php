<?php

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class ControllerTest extends \PhraseanetPHPUnitAbstract
{

    public function testGet()
    {
        $provider = \Alchemy\Phrasea\Vocabulary\Controller::get('User');

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Vocabulary\\ControlProvider\\UserProvider', $provider);

        try {
            $provider = \Alchemy\Phrasea\Vocabulary\Controller::get('Zebulon');
            $this->fail('Should raise an exception');
        } catch (\Exception $e) {

        }
    }

    public function testGetAvailable()
    {
        $available = \Alchemy\Phrasea\Vocabulary\Controller::getAvailable();

        $this->assertTrue(is_array($available));

        foreach ($available as $controller) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Vocabulary\\ControlProvider\\ControlProviderInterface', $controller);
        }
    }
}
