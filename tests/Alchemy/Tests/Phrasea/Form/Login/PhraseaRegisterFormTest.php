<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaRegisterForm;;
use Alchemy\Tests\Phrasea\Form\FormTestCase;
use Alchemy\Phrasea\Utilities\String\Camelizer;

class PhraseaRegisterFormTest extends FormTestCase
{
    protected function getForm()
    {
        $available = [
            'parameter' => [
                'type' => 'text',
                'label' => '',
            ]
        ];
        $params = [
            [
                'name'     => 'parameter',
                'required' => true
            ]
        ];

        return new PhraseaRegisterForm(self::$DI['app'], $available, $params, new Camelizer());
    }

    public function testFormDoesRegisterValidFields()
    {
        $available = array(
            'extra-parameter' => array(
                'type' => 'text',
                'label' => '',
            ),
            'extra-parameter2' => array(
                'type' => 'text',
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

        $expected = array('email', 'password', 'provider-id', '_token', 'extraParameter','extraParameter2');

        if (self::$DI['app']->hasTermsOfUse()) {
            $expected[] = 'accept-tou';
        }

        if (!self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            $expected[] = 'collections';
        }

        $form = new PhraseaRegisterForm(self::$DI['app'], $available, $params, new Camelizer());

        foreach (array_keys(self::$DI['app']->form($form)->createView()->vars['form']->children) as $name) {
            $this->assertContains($name, $expected);
        }
    }

    public function testFormDoesNotRegisterNonValidFields()
    {
        $available = array(
            'extra-parameter' => array(
                'type' => 'text',
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

        $expected = array('email', 'password', 'provider-id', '_token', 'extraParameter');

        if (self::$DI['app']->hasTermsOfUse()) {
            $expected[] = 'accept-tou';
        }

        if (!self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            $expected[] = 'collections';
        }

        $form = new PhraseaRegisterForm(self::$DI['app'], $available, $params, new Camelizer());

        foreach (array_keys(self::$DI['app']->form($form)->createView()->vars['form']->children) as $name) {
            $this->assertContains($name, $expected);
        }
    }
}
