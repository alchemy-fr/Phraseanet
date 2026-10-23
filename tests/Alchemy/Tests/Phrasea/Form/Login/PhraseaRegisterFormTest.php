<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaRegisterForm;
use Alchemy\Tests\Phrasea\Form\FormTestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PhraseaRegisterFormTest extends FormTestCase
{
    protected function getForm()
    {
        return new PhraseaRegisterForm();
    }

    protected function getFormOptions()
    {
        $available = [
            'parameter' => [
                'type' => TextType::class,
                'label' => '',
            ]
        ];
        $params = [
            [
                'name'     => 'parameter',
                'required' => true
            ]
        ];

        return [
            'app'                 => self::$DI['app'],
            'available_fields'    => $available,
            'registration_fields' => $params,
        ];
    }

    public function testFormDoesRegisterValidFields()
    {
        $available = array(
            'extra-parameter' => array(
                'type' => TextType::class,
                'label' => '',
            ),
            'extra-parameter2' => array(
                'type' => TextType::class,
                'label' => '',
            )
        );
        $params = array(
            array(
                'name'     => 'extra-parameter',
                'required' => true
            ),
            array(
                'name'     => 'extra-parameter2',
                'required' => true
            )
        );

        $expected = array('email', 'password', 'provider-id', 'captcha', '_token', 'extraParameter','extraParameter2');

        if (self::$DI['app']->hasTermsOfUse()) {
            $expected[] = 'accept-tou';
        }

        if (!self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            $expected[] = 'collections';
        }

        $form = self::$DI['app']->form(PhraseaRegisterForm::class, null, [
            'app'                 => self::$DI['app'],
            'available_fields'    => $available,
            'registration_fields' => $params,
        ]);

        foreach (array_keys($form->createView()->children) as $name) {
            $this->assertContains($name, $expected);
        }
    }

    public function testFormDoesNotRegisterNonValidFields()
    {
        $available = array(
            'extra-parameter' => array(
                'type' => TextType::class,
                'label' => '',
            )
        );
        $params = array(
            array(
                'name'     => 'extra-parameter',
                'required' => true
            ),
            array(
                'name'     => 'extra-parameter2',
                'required' => true
            )
        );

        $expected = array('email', 'password', 'provider-id', 'captcha', '_token', 'extraParameter');

        if (self::$DI['app']->hasTermsOfUse()) {
            $expected[] = 'accept-tou';
        }

        if (!self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            $expected[] = 'collections';
        }

        $form = self::$DI['app']->form(PhraseaRegisterForm::class, null, [
            'app'                 => self::$DI['app'],
            'available_fields'    => $available,
            'registration_fields' => $params,
        ]);

        foreach (array_keys($form->createView()->children) as $name) {
            $this->assertContains($name, $expected);
        }
    }
}
