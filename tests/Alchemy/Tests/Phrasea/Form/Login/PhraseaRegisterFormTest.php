<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaRegisterForm;;
use Alchemy\Tests\Phrasea\Form\FormTestCase;
use Alchemy\Phrasea\Utilities\String\Camelizer;

class PhraseaRegisterFormTest extends FormTestCase
{
    protected function getForm()
    {
        $available = array(
            'parameter' => array(
                'type' => 'text',
                'label' => 'Yollah !',
            )
        );
        $params = array(
            array(
                'name'     => 'parameter',
                'required' => true
            )
        );

        return new PhraseaRegisterForm(self::$DI['app'], $available, $params, new Camelizer());
    }

    public function testFormDoesRegisterValidFields()
    {
        $available = array(
            'parameter' => array(
                'type' => 'text',
                'label' => 'Yollah !',
            ),
            'parameter2' => array(
                'type' => 'text',
                'label' => 'Yollah !',
            )
        );
        $params = array(
            array(
                'name'     => 'parameter',
                'required' => true
            ),
            array(
                'name'     => 'parameter2',
                'required' => true
            )
        );

        $form = new PhraseaRegisterForm(self::$DI['app'], $available, $params, new Camelizer());

        $this->assertCount(8, self::$DI['app']->form($form)->createView()->vars['form']->children);
    }

    public function testFormDoesNotRegisterNonValidFields()
    {
        $available = array(
            'parameter' => array(
                'type' => 'text',
                'label' => 'Yollah !',
            )
        );
        $params = array(
            array(
                'name'     => 'parameter',
                'required' => true
            ),
            array(
                'name'     => 'parameter2',
                'required' => true
            )
        );

        $form = new PhraseaRegisterForm(self::$DI['app'], $available, $params, new Camelizer());

        $this->assertCount(7, self::$DI['app']->form($form)->createView()->vars['form']->children);
    }
}
