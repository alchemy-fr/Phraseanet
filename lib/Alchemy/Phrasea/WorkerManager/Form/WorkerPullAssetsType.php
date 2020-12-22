<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkerPullAssetsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        // because this form will have 3 submit buttons - to use the same route -, this "act" field
        // will reflect the value of the clicked button (js)
        // !!! tried: using symfony "getClickedButton()" does to NOT work (submit button values seems not sent in request ?)
        $builder
            ->add('act', HiddenType::class, [
                'attr' => [
                    'class' => 'act'
                ]
            ]);

        $builder
            ->add('UploaderApiBaseUri', TextType::class, [
                'label' => 'admin::workermanager:tab:pullassets: Uploader api base uri'
            ])
            ->add('clientSecret', TextType::class, [
                'label' => 'admin::workermanager:tab:pullassets: Client secret'
            ])
            ->add('clientId', TextType::class, [
                'label' => 'admin::workermanager:tab:pullassets: Client ID'
            ])
            ->add('pullInterval', TextType::class, [
                'label' => 'admin::workermanager:tab:pullassets: Fetching interval in second'
            ]);

        $builder
            ->add("boutton::appliquer", SubmitType::class, [
                'label' => "boutton::appliquer",
                'attr' => ['value' => 'save']
            ]);

    }

    public function getName()
    {
        return 'worker_pullAssets';
    }
}
