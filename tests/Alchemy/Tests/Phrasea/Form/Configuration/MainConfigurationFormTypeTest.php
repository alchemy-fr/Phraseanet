<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Form\Configuration\MainConfigurationFormType;
use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Validator\Constraints as Assert;
use Alchemy\Tests\Phrasea\Form\FormTestCase;

class MainConfigurationFormTypeTest extends FormTestCase
{
    public function getForm()
    {
        return new MainConfigurationFormType(array('fr_FR' => 'french'), self::$DI['app']['url_generator']);
    }
}
