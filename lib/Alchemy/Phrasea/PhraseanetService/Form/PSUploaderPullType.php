<?php

namespace Alchemy\Phrasea\PhraseanetService\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class PSUploaderPullType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('pullmodeUri', TextType::class, [
                'label' => 'admin:phrasea-service-setting:tab:uploader:: pull mode uri',
                'attr' => [
                    'class' => 'input-xxlarge',
                    'placeholder' => 'https://api-uploader.phrasea.local/commits?target=b6b9ea65-aecb-401b-9bff-1d29ba69a253'
                ]
            ])
            ->add('clientSecret', TextType::class, [
                'label' => 'admin:phrasea-service-setting:tab:uploader:: Client secret',
                'attr'  => [
                    'class' => 'input-xxlarge',
                    'placeholder' => 'secret'
                ]
            ])
            ->add('clientId', TextType::class, [
                'label' => 'admin:phrasea-service-setting:tab:uploader:: Client ID',
                'attr'  => [
                    'class' => 'input-xxlarge',
                    'placeholder' => 'client_id'
                ]
            ])
            ->add('verify_ssl', CheckboxType::class, [
                'label'    => 'admin:phrasea-service-setting:tab:uploader:: verify ssl',
                'required' => false
            ])
            ->add('target_name', TextType::class, [
                'label' =>  'admin:phrasea-service-setting:tab:uploader:: target Name',
                'attr' => [
                    'class' => 'target-name'
                ]
            ])
        ;

    }

    public function getName()
    {
        return 'ps_pullAssets';
    }
}
