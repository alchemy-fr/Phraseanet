<?php

namespace Alchemy\Phrasea\PhraseanetService\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;

class PSExposeConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('activate_service_expose', CheckboxType::class, [
                'label'    => 'admin:phrasea-service-setting:tab:expose:: activate Phraseanet-service expose',
                'required' => false,
                'attr'      => [
                    'class' => 'activate-expose'
                ]
            ])
            ->add('expose_connections', CollectionType::class, [
                'label'         => false,
                'entry_type'    => PSExposeConnectionType::class,
                'prototype'     => true,
                'allow_add'     => true,
                'allow_delete'  => true,
            ])
        ;
    }

    public function getName()
    {
        return 'ps_expose_configuration';
    }
}
