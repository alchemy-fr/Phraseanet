<?php

namespace Alchemy\Tests\Phrasea\Form;

use Alchemy\Phrasea\Form\TaskForm;

class TaskFormTest extends FormTestCase
{
    protected function getForm()
    {
        return new TaskForm(self::$DI['app']);
    }
}
