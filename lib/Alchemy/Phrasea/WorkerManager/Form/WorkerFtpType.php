<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkerFtpType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('proxy', 'text', [
                'label' => 'admin::workermanager:tab:ftp: Proxy',
                'required' => false
            ])
            ->add('proxyPort', 'text', [
                'label' => 'admin::workermanager:tab:ftp: Proxy port',
                'required' => false
            ])
            ->add('proxyUser', 'text', [
                'label' => 'admin::workermanager:tab:ftp: Proxy user',
                'required' => false
            ])
            ->add('proxyPassword', 'text', [
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
