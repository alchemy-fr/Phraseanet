<?php

namespace Alchemy\Tests\Phrasea\Form\Login;

use Alchemy\Phrasea\Form\Login\PhraseaRegisterForm;;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

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

        return new PhraseaRegisterForm(self::$DI['app'], $available, $params, new \Alchemy\Phrasea\Utilities\String\Camelizer());
    }
}
