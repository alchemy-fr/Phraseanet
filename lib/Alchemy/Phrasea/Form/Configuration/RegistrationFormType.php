<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\Constraints as Assert;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_autoselectDB', 'checkbox', array(
            'label'        => _('Auto select databases'),
            'data'         => true,
            'help_message' => _('This option disables the selecting of the databases on which a user can register himself, and registration is made on all granted databases.'),
        ));
        $builder->add('GV_autoregister', 'checkbox', array(
            'label'        => _('Enable auto registration'),
            'data'         => false,
        ));
    }

    public function getName()
    {
        return null;
    }
}
