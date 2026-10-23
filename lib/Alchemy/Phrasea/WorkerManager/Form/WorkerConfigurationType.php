<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkerConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        /** @var AMQPConnection $AMQPConnection */
        $AMQPConnection = $options['amqp_connection'];

        $g = $builder->create("queues", FormType::class, ['attr'=>['class'=>'form-row']]);

        foreach($AMQPConnection->getBaseQueueNames() as $baseQueueName) {
            if($AMQPConnection->hasRetryQueue($baseQueueName)
                || $AMQPConnection->hasLoopQueue($baseQueueName)
                || $AMQPConnection->hasDelayedQueue($baseQueueName)
            ) {
                $g->add($baseQueueName, QueueSettingsType::class, [
                    'attr'            => ['class' => 'norow'],
                    'block_name'      => 'queue',
                    'amqp_connection' => $AMQPConnection,
                    'base_queue_name' => $baseQueueName,
                ]);
            }
        }

        $builder->add($g);

        $builder->add("boutton::appliquer", SubmitType::class,
        [
            'label' => "boutton::appliquer"
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('amqp_connection');
        $resolver->setAllowedTypes('amqp_connection', AMQPConnection::class);
    }

    public function getBlockPrefix(): string
    {
        return 'worker_configuration';
    }
}
