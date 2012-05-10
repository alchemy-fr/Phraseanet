<?php

require_once __DIR__ . '/../../../../PhraseanetPHPUnitAbstract.class.inc';

class ServiceAbstractTest extends PhraseanetPHPUnitAbstract
{
    /**
     *
     * @var \Alchemy\Phrasea\Core\Service\ServiceAbstract
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $stub = $this->getMockForAbstractClass(
            "\Alchemy\Phrasea\Core\Service\ServiceAbstract"
            , array(
            self::$core
            , array('option' => 'my_options')
            )
        );

        $this->object = $stub;
    }

    public function testGetOptions()
    {
        $this->assertTrue(is_array($this->object->getOptions()));
        $this->assertEquals(array('option' => 'my_options'), $this->object->getOptions());
    }
}
