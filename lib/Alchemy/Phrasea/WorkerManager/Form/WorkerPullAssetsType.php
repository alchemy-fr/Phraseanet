<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkerPullAssetsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('endpointCommit', 'text', [
                'label' => 'Endpoint get commit'
            ])
            ->add('endpointToken', 'text', [
                'label' => 'Endpoint get token'
            ])
            ->add('clientSecret', 'text', [
                'label' => 'Client secret'
            ])
            ->add('clientId', 'text', [
                'label' => 'Client ID'
            ])
            ->add('pullInterval', 'text', [
                'label' => 'Fetching interval in second'
            ])
        ;
    }

    public function getName()
    {
        return 'worker_pullAssets';
    }
}
