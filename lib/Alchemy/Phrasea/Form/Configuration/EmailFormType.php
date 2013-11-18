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

class EmailFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_defaulmailsenderaddr', 'text', array(
            'label'        => _('Default mail sender address'),
            'data'         => 'phraseanet@example.com',
        ));
        $builder->add('GV_email_prefix', 'text', array(
            'label'        => _('Prefix for notification emails'),
        ));
        $builder->add('GV_smtp', 'checkbox', array(
            'label'        => _('Use a SMTP server'),
        ));
        $builder->add('GV_smtp_auth', 'checkbox', array(
            'label'        => _('Enable SMTP authentication'),
        ));
        $builder->add('GV_smtp_host', 'text', array(
            'label'        => _('SMTP host'),
        ));
        $builder->add('GV_smtp_port', 'text', array(
            'label'        => _('SMTP port'),
        ));
        $builder->add('GV_smtp_secure', 'choice', array(
            'label'        => _('SMTP encryption'),
            'data'         => 'tls',
            'choices'      => array('none' => _('None'), 'ssl' => 'SSL', 'tls' => 'TLS'),
        ));
        $builder->add('GV_smtp_user', 'text', array(
            'label'        => _('SMTP user'),
        ));
        $builder->add('GV_smtp_password', 'text', array(
            'label'        => _('SMTP password'),
        ));
    }

    public function getName()
    {
        return null;
    }
}
