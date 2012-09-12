<?php

namespace Alchemy\Phrasea\Vocabulary;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class ControllerTest extends \PhraseanetPHPUnitAbstract
{

    public function testGet()
    {
        $provider = Controller::get(self::$application, 'User');

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Vocabulary\\ControlProvider\\UserProvider', $provider);

        try {
            $provider = Controller::get(self::$application, 'Zebulon');
            $this->fail('Should raise an exception');
        } catch (\Exception $e) {

        }
    }

    public function testGetAvailable()
    {
        $available = Controller::getAvailable(self::$application);

        $this->assertTrue(is_array($available));

        foreach ($available as $controller) {
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Vocabulary\\ControlProvider\\ControlProviderInterface', $controller);
        }
    }
}
