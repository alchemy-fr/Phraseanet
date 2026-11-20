<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form;

use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TaskForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label'       => 'Task name',
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(),
            ],
        ]);
        $builder->add('period', IntegerType::class, [
            'label'       => 'Task period (in seconds)',
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\GreaterThan(value: 0),
            ],
        ]);
        $builder->add('status', ChoiceType::class, [
            'label'       => 'The task status',
            'choices'   => [
                'Started' => Task::STATUS_STARTED,
                'Stopped' => Task::STATUS_STOPPED,
            ],
        ]);
        $builder->add('settings', HiddenType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Task::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
