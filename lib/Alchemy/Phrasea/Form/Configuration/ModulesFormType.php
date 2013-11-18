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

class ModulesFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_thesaurus', 'checkbox', array(
            'label'        => _('Enable thesaurus'),
            'data'         => true,
        ));
        $builder->add('GV_multiAndReport', 'checkbox', array(
            'label'        => _('Enable multi-doc mode'),
            'data'         => true,
        ));
        $builder->add('GV_seeOngChgDoc', 'checkbox', array(
            'label'        => _('Enable HD substitution'),
            'data'         => true,
        ));
        $builder->add('GV_seeNewThumb', 'checkbox', array(
            'label'        => _('Enable thumbnail substitution'),
            'data'         => true,
        ));
    }

    public function getName()
    {
        return null;
    }
}
