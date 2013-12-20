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
                'label' => 'Yollah !',
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
        $available = [
            'parameter' => [
                'type' => 'text',
                'label' => 'Yollah !',
            ],
            'parameter2' => [
                'type' => 'text',
                'label' => 'Yollah !',
            ]
        ];
        $params = [
            [
                'name'     => 'parameter',
                'required' => true
            ],
            [
                'name'     => 'parameter2',
                'required' => true
            ]
        ];

        $form = new PhraseaRegisterForm(self::$DI['app'], $available, $params, new Camelizer());

        $this->assertCount(self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections']) ? 7 : 8, self::$DI['app']->form($form)->createView()->vars['form']->children);
    }

    public function testFormDoesNotRegisterNonValidFields()
    {
        $available = [
            'parameter' => [
                'type' => 'text',
                'label' => 'Yollah !',
            ]
        ];
        $params = [
            [
                'name'     => 'parameter',
                'required' => true
            ],
            [
                'name'     => 'parameter2',
                'required' => true
            ]
        ];

        $form = new PhraseaRegisterForm(self::$DI['app'], $available, $params, new Camelizer());

        $this->assertCount(self::$DI['app']['conf']->get(['registry', 'registration', 'auto-select-collections']) ? 6 : 7, self::$DI['app']->form($form)->createView()->vars['form']->children);
    }
}
