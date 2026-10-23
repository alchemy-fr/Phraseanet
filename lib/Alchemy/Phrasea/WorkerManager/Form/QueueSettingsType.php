<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QueueSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        /** @var AMQPConnection $AMQPConnection */
        $AMQPConnection = $options['amqp_connection'];
        $baseQueueName = $options['base_queue_name'];

        $builder->add('n_workers', HiddenType::class, [
            'label'    => 'admin::workermanager:tab:workerconfig:n_workers',
            'required' => false,
            'attr' => [
                'placeholder' => 1
            ]
        ]);
        if($AMQPConnection->hasRetryQueue($baseQueueName) || $AMQPConnection->hasLoopQueue($baseQueueName)) {
            $builder
                ->add('max_retry', IntegerType::class, [
                    'label'    => 'admin::workermanager:tab:workerconfig:max retry',
                    'required' => false,
                    'attr' => [
                        'placeholder' => $AMQPConnection->getDefaultSetting($baseQueueName, AMQPConnection::MAX_RETRY),
                        //'class'=>'col'
                    ]
                ])
                ->add('ttl_retry', IntegerType::class, [
                    'label'    => 'admin::workermanager:tab:workerconfig:retry delay in seconds',
                    'required' => false,
                    'attr' => [
                        'placeholder' => $AMQPConnection->getDefaultSetting($baseQueueName, AMQPConnection::TTL_RETRY) / 1000.0,
                        //'class'=>'col'
                    ]
                ]);
        }
        if($AMQPConnection->hasDelayedQueue($baseQueueName)) {
            $builder->add('ttl_delayed', IntegerType::class, [
                'label'    => 'admin::workermanager:tab:workerconfig:delayed delay in seconds',
                'required' => false,
                'attr' => [
                    'placeholder' => $AMQPConnection->getDefaultSetting($baseQueueName, AMQPConnection::TTL_DELAYED) / 1000.0,
                    //'class'=>'col'
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['amqp_connection', 'base_queue_name']);
        $resolver->setAllowedTypes('amqp_connection', AMQPConnection::class);
        $resolver->setAllowedTypes('base_queue_name', 'string');
    }

    public function getBlockPrefix(): string
    {
        return 'queue_settings';
    }
}
