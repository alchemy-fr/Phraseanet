<?php

namespace Alchemy\Tests\Phrasea\Form;

use Symfony\Component\Form\FormInterface;

abstract class FormTestCase extends \PhraseanetTestCase
{
    public function testBuildForm()
    {
        $form = $this->getForm();

        $builder = $this->createStub('Symfony\Component\Form\FormBuilder');

        $form->buildForm($builder, array_merge(['disabled' => false], $this->getFormOptions()));
    }

    public function testCreateForm()
    {
        $form = self::$DI['app']->form(get_class($this->getForm()), null, $this->getFormOptions());

        $this->assertInstanceOf(FormInterface::class, $form);
        $this->assertSame('', $form->getName());
    }

    public function testGetBlockPrefix()
    {
        $form = $this->getForm();
        $this->assertSame('', $form->getBlockPrefix());
    }

    abstract protected function getForm();

    protected function getFormOptions()
    {
        return [];
    }
}
