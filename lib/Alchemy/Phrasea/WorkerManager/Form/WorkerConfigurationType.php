<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
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
            /*
            $g = null;
            if($this->AMQPConnection->hasRetryQueue($baseQueueName) || $this->AMQPConnection->hasLoopQueue($baseQueueName)) {
                $g = $g ?? $this->createFormGroup($builder, $baseQueueName);
                $g->add('max_retry', TextType::class, [
                    'label' => 'admin::workermanager:tab:workerconfig:max retry',
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->AMQPConnection->getMaxRetry($baseQueueName)
                    ]
                ]);
                $g->add('ttl_retry', TextType::class, [
                    'label' => 'admin::workermanager:tab:workerconfig:retry delay in ms',
                    'required' => false,
                    'attr' => [
                       'placeholder' => $this->AMQPConnection->getTTLRetry($baseQueueName)
                    ]
                ]);
            }
            if($this->AMQPConnection->hasDelayedQueue($baseQueueName)) {
                $g = $g ?? $this->createFormGroup($builder, $baseQueueName);
                $g->add('ttl_delayed', TextType::class, [
                    'label' => 'admin::workermanager:tab:workerconfig:delayed delay in ms',
                    'required' => false,
                    'attr' => [
                        'placeholder' => $this->AMQPConnection->getTTLDelayed($baseQueueName)
                    ]
                ]);
            }
            if($g) {
//                $builder->add($g);
            }
            */
            if($this->AMQPConnection->hasRetryQueue($baseQueueName)
                || $this->AMQPConnection->hasLoopQueue($baseQueueName)
                || $this->AMQPConnection->hasDelayedQueue($baseQueueName)
            ) {
                $f = new QueueSettingsType($this->AMQPConnection, $baseQueueName);
                $builder->add($baseQueueName, $f, ['attr' => ['class' => 'norow'], 'block_name' => 'queue']);
            }
        }
        $builder->add("boutton::appliquer", SubmitType::class,
        [
            'label' => "boutton::appliquer"
        ]);
    }

    /*
    private function createFormGroup(FormBuilderInterface $builder, string $name)
    {
        // todo : fix form render : one horizontal block per queue
        return $builder->create($name, FormType::class, ['attr'=>['class'=>'form-row']]);
    }
    */

    public function getName()
    {
        return 'worker_configuration';
    }
}
