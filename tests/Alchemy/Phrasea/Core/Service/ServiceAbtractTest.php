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

        $this->object = $this->getMockForAbstractClass(
            "\Alchemy\Phrasea\Core\Service\ServiceAbstract"
            , array(
            self::$application
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
