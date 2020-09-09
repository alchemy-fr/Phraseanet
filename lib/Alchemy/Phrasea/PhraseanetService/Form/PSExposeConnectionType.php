<?php

namespace Alchemy\Phrasea\PhraseanetService\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PSExposeConnectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('activate_expose', CheckboxType::class, [
                'label'    => 'admin:phrasea-service-setting:tab:expose:: Activate this expose',
                'required' => false
            ])
            ->add('auth_connection_kind', CheckboxType::class, [
                'label'    => 'admin:phrasea-service-setting:tab:expose:: Connection Kind',
                'required' => false,
                'data'     => true,
                'attr' => [
                    'class' => 'auth-connection'
                ]
            ])
            ->add('expose_name', TextType::class, [
                'label' =>  'admin:phrasea-service-setting:tab:expose:: Name',
                'attr' => [
                    'class' => 'expose-name'
                ]
            ])
            ->add('expose_front_uri', TextType::class, [
                'label' => 'admin:phrasea-service-setting:tab:expose:: Expose Front base uri',
                'attr'  => [
                    'class' => 'input-xxlarge'
                ]
            ])
            ->add('expose_base_uri', TextType::class, [
                'label' => 'admin:phrasea-service-setting:tab:expose:: Base Uri Expose api',
                'attr'  => [
                    'class' => 'input-xxlarge'
                ]
            ])
            ->add('client_secret', TextType::class, [
                'label' => 'admin:phrasea-service-setting:tab:expose:: Client secret',
                'attr'  => [
                    'class' => 'input-xxlarge'
                ]
            ])
            ->add('client_id', TextType::class, [
                'label' => 'admin:phrasea-service-setting:tab:expose:: Client ID',
                'attr'  => [
                    'class' => 'input-xxlarge'
                ]
            ])
        ;
    }

    public function getName()
    {
        return 'ps_expose_connection';
    }
}
