<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\RegistryManipulator;

class RegistryManipulatorTest extends \PhraseanetTestCase
{
    /** @var RegistryManipulator */
    private $manipulator;

    public function setUp()
    {
        parent::setUp();
        $this->manipulator = new RegistryManipulator(self::$DI['app']['form.factory'], self::$DI['app']['translator'], self::$DI['app']['locales.available']);
    }

    public function testCreateForm()
    {
        $form = $this->manipulator->createForm();
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
        $data = $form->getData();

        $this->assertEquals('Phraseanet', $data['general']['title']);
    }

    public function testCreateFormWithConf()
    {
        $conf = $this->getMockBuilder('Alchemy\Phrasea\Core\Configuration\PropertyAccess')
            ->disableOriginalConstructor()
            ->getMock();
        $conf->expects($this->once())
            ->method('get')
            ->with('registry')
            ->will($this->returnValue(['general' => ['title' => 'Grumpf']]));

        $form = $this->manipulator->createForm($conf);
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
        $data = $form->getData();

        $this->assertEquals('Grumpf', $data['general']['title']);
    }

    public function testGetRegistryData()
    {
        $data = $this->manipulator->getRegistryData();
        $this->assertInternalType('array', $data);
        $this->assertEquals('Phraseanet', $data['general']['title']);
    }

    public function testGetRegistryDataWithForm()
    {
        $form = $this->manipulator->createForm();
        $form->submit(['general' => ['title' => 'Grumpf']]);

        $data = $this->manipulator->getRegistryData($form);
        $this->assertInternalType('array', $data);
        $this->assertEquals('Grumpf', $data['general']['title']);
    }
}
