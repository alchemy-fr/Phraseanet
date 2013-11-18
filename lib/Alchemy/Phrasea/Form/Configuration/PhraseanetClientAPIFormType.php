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

class PhraseanetClientAPIFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_client_navigator', 'checkbox', array(
            'label'        => _('Authorize *Phraseanet Navigator*'),
            'data'         => true,
            'help_message' => _('*Phraseanet Navigator* is a smartphone application that allow user to connect on this instance'),
        ));

        $builder->add('GV_client_officeplugin', 'checkbox', array(
            'label'        => _('Authorize Microsoft Office Plugin to connect.'),
            'data'         => true,
        ));
    }

    public function getName()
    {
        return null;
    }
}
