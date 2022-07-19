<?php

namespace Alchemy\Phrasea\PhraseanetService\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

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
            ->add('verify_ssl', CheckboxType::class, [
                'label'    => 'admin:phrasea-service-setting:tab:expose:: verify ssl',
                'required' => false
            ])
            ->add('connection_kind', ChoiceType::class, [
                'label'    => 'admin:phrasea-service-setting:tab:expose:: Connection Kind',
                'required' => true,
                'attr' => [
                    'class' => 'auth-connection'
                ],
                'choices' => [
                    'client_credentials'    => 'client_credentials',
                    'password'              => 'password'
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
                'label' => 'admin:phrasea-service-setting:tab:expose:: Expose Base Uri api',
                'attr'  => [
                    'class' => 'input-xxlarge'
                ]
            ])
            ->add('expose_client_secret', TextType::class, [
                'label'     => 'admin:phrasea-service-setting:tab:expose:: Expose Client secret',
                'required'  => false,
                'attr'      => [
                    'class' => 'input-xxlarge'
                ]
            ])
            ->add('expose_client_id', TextType::class, [
                'label'     => 'admin:phrasea-service-setting:tab:expose:: Expose Client ID',
                'required'  => false,
                'attr'      => [
                    'class' => 'input-xxlarge'
                ]
            ])
            ->add('auth_base_uri', TextType::class, [
                'label'     => 'admin:phrasea-service-setting:tab:expose:: Auth Base Uri ',
                'required'  => false,
                'attr'      => [
                    'class' => 'input-xxlarge'
                ]
            ])
            ->add('auth_client_secret', TextType::class, [
                'label'     => 'admin:phrasea-service-setting:tab:expose:: Auth Client secret',
                'required'  => false,
                'attr'      => [
                    'class' => 'input-xxlarge'
                ]
            ])
            ->add('auth_client_id', TextType::class, [
                'label'     => 'admin:phrasea-service-setting:tab:expose:: Auth Client ID',
                'required'  => false,
                'attr'      => [
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
