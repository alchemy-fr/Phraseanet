<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkerRecordMoverType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('act', HiddenType::class, [
                'attr' => [
                    'class' => 'act'
                ]
            ])
            ->add('ttl_retry', TextType::class, [
                'label' => 'admin::workermanager:tab:record mover: period in second'
            ])
            ->add('xmlSetting', TextareaType::class, [
                'label' => 'admin::workermanager:tab:record mover: xml view'
            ])
            ->add("apply", SubmitType::class, [
                'label' => "boutton::appliquer",
                'attr' => ['value' => 'save']
            ])
        ;
    }

    public function getName()
    {
        return 'worker_recordMover';
    }
}
