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
use Symfony\Component\Validator\Constraints as Assert;

class MaintenanceFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_message', 'text', array(
            'label'       => _('Maintenance message'),
            'data'        => 'The application is down for maintenance',
        ));
        $builder->add('GV_message_on', 'checkbox', array(
            'label'       => _('Enable maintenance message broadcast'),
            'data'        => false,
        ));
        $builder->add('GV_log_errors', 'checkbox', array(
            'label'       => _('Log errors'),
            'data'        => false,
        ));
    }

    public function getName()
    {
        return null;
    }
}
