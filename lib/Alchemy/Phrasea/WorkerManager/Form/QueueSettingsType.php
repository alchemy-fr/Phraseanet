<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

class QueueSettingsType extends AbstractType
{
    private $AMQPConnection;
    private $baseQueueName;

    public function __construct(AMQPConnection $AMQPConnection, string $baseQueueName)
    {
        $this->AMQPConnection = $AMQPConnection;
        $this->baseQueueName  = $baseQueueName;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('n_workers', HiddenType::class, [
            'label'    => 'admin::workermanager:tab:workerconfig:n_workers',
            'required' => false,
            'attr' => [
                'placeholder' => 1
            ]
        ]);
        if($this->AMQPConnection->hasRetryQueue($this->baseQueueName) || $this->AMQPConnection->hasLoopQueue($this->baseQueueName)) {
            $builder
                ->add('max_retry', IntegerType::class, [
                    'label'    => 'admin::workermanager:tab:workerconfig:max retry',
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->AMQPConnection->getDefaultSetting($this->baseQueueName, AMQPConnection::MAX_RETRY),
                        //'class'=>'col'
                    ]
                ])
                ->add('ttl_retry', IntegerType::class, [
                    'label'    => 'admin::workermanager:tab:workerconfig:retry delay in seconds',
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->AMQPConnection->getDefaultSetting($this->baseQueueName, AMQPConnection::TTL_RETRY) / 1000.0,
                        //'class'=>'col'
                    ]
                ]);
        }
        if($this->AMQPConnection->hasDelayedQueue($this->baseQueueName)) {
            $builder->add('ttl_delayed', IntegerType::class, [
                'label'    => 'admin::workermanager:tab:workerconfig:delayed delay in seconds',
                'required' => false,
                'attr' => [
                    'placeholder' => $this->AMQPConnection->getDefaultSetting($this->baseQueueName, AMQPConnection::TTL_DELAYED) / 1000.0,
                    //'class'=>'col'
                ]
            ]);
        }
    }

    public function getName()
    {
        return 'queue_settings';
    }
}
