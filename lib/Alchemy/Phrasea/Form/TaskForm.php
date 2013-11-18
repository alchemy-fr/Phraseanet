<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form;

use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TaskForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', 'text', [
            'label'       => _('Task name'),
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);
        $builder->add('period', 'integer', [
            'label'       => _('Task period (in seconds)'),
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\GreaterThan(['value' => 0]),
            ],
        ]);
        $builder->add('status', 'choice', [
            'label'       => _('The task status'),
            'choices'   => [
                Task::STATUS_STARTED   => 'Started',
                Task::STATUS_STOPPED   => 'Stopped',
            ],
        ]);
        $builder->add('settings', 'hidden');
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Alchemy\Phrasea\Model\Entities\Task',
        ]);
    }

    public function getName()
    {
        return null;
    }
}
