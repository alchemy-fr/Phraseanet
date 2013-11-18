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

class DisplayFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_adminMail', 'text', array(
            'label'        => _('Admin email'),
        ));
        $builder->add('GV_view_bas_and_coll', 'checkbox', array(
            'label'        => _('Display the name of databases and collections'),
            'data'         => true,
        ));
        $builder->add('GV_choose_export_title', 'checkbox', array(
            'label'        => _('Choose the title of the document to export'),
            'data'         => false,
        ));
        $builder->add('GV_default_export_title', 'choice', array(
            'label'        => _('Default export title'),
            'data'         => 'title',
            'choices'      => array('title' => _('Document title'), 'original' => _('Original name')),
        ));
        $builder->add('GV_social_tools', 'choice', array(
            'label'        => _('Enable this setting to share on Facebook and Twitter'),
            'data'         => 'none',
            'choices'      => array('none' => _('Disabled'), 'publishers' => _('Publishers'), 'all' => _('Enabled')),
        ));
    }

    public function getName()
    {
        return null;
    }
}
