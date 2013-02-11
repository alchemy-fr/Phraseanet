<?php

namespace Alchemy\Tests\Phrasea\Core\Service;

use Alchemy\Phrasea\Core\Service\ServiceAbstract;

class ServiceAbstractTest extends \PhraseanetPHPUnitAbstract
{
    /**
     *
     * @var \Alchemy\Phrasea\Core\Service\ServiceAbstract
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();

        $this->object = $this->getMockForAbstractClass(
            "\Alchemy\Phrasea\Core\Service\ServiceAbstract"
            , array(
            self::$DI['app']
            , array('option' => 'my_options')
            )
        );
    }

    public function testGetOptions()
    {
        $this->assertTrue(is_array($this->object->getOptions()));
        $this->assertEquals(array('option' => 'my_options'), $this->object->getOptions());
    }
}
