<?php

namespace Alchemy\Tests\Phrasea\Form;

abstract class FormTestCase extends \PhraseanetPHPUnitAbstract
{
    public function testBuildForm()
    {
        $form = $this->getForm();

        $builder = $this
            ->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $form->buildForm($builder, ['disabled' => false]);
    }

    public function testGetName()
    {
        $form = $this->getForm();
        $this->assertNull($form->getName());
    }

    abstract protected function getForm();
}
