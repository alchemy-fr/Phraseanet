<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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

        $g = $builder->create("queues", FormType::class, ['attr'=>['class'=>'form-row']]);

        foreach($this->AMQPConnection->getBaseQueueNames() as $baseQueueName) {
            if($this->AMQPConnection->hasRetryQueue($baseQueueName)
                || $this->AMQPConnection->hasLoopQueue($baseQueueName)
                || $this->AMQPConnection->hasDelayedQueue($baseQueueName)
            ) {
                $f = new QueueSettingsType($this->AMQPConnection, $baseQueueName);
                $g->add($baseQueueName, $f, ['attr' => ['class' => 'norow'], 'block_name' => 'queue']);
            }
        }

        $builder->add($g);

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
