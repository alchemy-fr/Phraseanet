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

class PushFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_validation_reminder', 'integer', array(
            'label'       => _('Number of days before the end of the validation to send a reminder email'),
            'data'        => 2,
        ));
        $builder->add('GV_val_expiration', 'integer', array(
            'label'        => _('Default validation links duration'),
            'data'         => 10,
            'help_message' => _('If set to 0, duration is permanent'),
        ));
    }

    public function getName()
    {
        return null;
    }
}
