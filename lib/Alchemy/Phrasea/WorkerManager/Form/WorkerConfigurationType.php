<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkerConfigurationType extends AbstractType
{
    private $AMQPConnection;

    public function __construct(AMQPConnection $AMQPConnection)
    {
        $this->AMQPConnection = $AMQPConnection;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        foreach($this->AMQPConnection->getBaseQueueNames() as $baseQueueName) {
            $g = null;
            if($this->AMQPConnection->hasRetryQueue($baseQueueName)) {
                $g = $g ?? $builder->create($baseQueueName, FormType::class);
                $g->add('max_retry', TextType::class, [
                    'label' => 'admin::workermanager:tab:workerconfig: '.$baseQueueName.' max retry',
                    'attr' => [
                        'placeholder' => $this->AMQPConnection->getMaxRetry($baseQueueName)
                    ]
                ]);
                $g->add('ttl_retry', TextType::class, [
                    'label' => 'admin::workermanager:tab:workerconfig: '.$baseQueueName.' retry delay in ms',
                    'attr' => [
                       'placeholder' => $this->AMQPConnection->getTTLRetry($baseQueueName)
                    ]
                ]);
            }
            if($this->AMQPConnection->hasDelayedQueue($baseQueueName)) {
                $g = $g ?? $builder->create($baseQueueName, FormType::class);
                $g->add('ttl_delayed', TextType::class, [
                    'label' => 'admin::workermanager:tab:workerconfig: '.$baseQueueName.' delayed delay in ms',
                    'attr' => [
                        'placeholder' => $this->AMQPConnection->getTTLDelayed($baseQueueName)
                    ]
                ]);
            }
            if($g) {
                $builder->add($g);
            }
        }
        $builder->add("boutton::appliquer", SubmitType::class,
        [
            'label' => "boutton::appliquer"
        ]);
    }

    public function getName()
    {
        return 'worker_configuration';
    }
}
