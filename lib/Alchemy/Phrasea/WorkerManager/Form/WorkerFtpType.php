<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkerFtpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('proxy', TextType::class, [
                'label' => 'admin::workermanager:tab:ftp: Proxy',
                'required' => false
            ])
            ->add('proxyPort', TextType::class, [
                'label' => 'admin::workermanager:tab:ftp: Proxy port',
                'required' => false
            ])
            ->add('proxyUser', TextType::class, [
                'label' => 'admin::workermanager:tab:ftp: Proxy user',
                'required' => false
            ])
            ->add('proxyPassword', TextType::class, [
                'label' => 'admin::workermanager:tab:ftp: Proxy password',
                'required' => false
            ])
        ;
    }

    public function getName()
    {
        return 'worker_ftp';
    }
}
