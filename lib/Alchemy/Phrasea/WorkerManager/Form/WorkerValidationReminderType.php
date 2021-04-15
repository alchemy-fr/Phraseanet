<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class WorkerValidationReminderType extends AbstractType
{
    private $AMQPConnection;

    public function __construct(AMQPConnection $AMQPConnection)
    {
        $this->AMQPConnection = $AMQPConnection;
    }

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

        // every ttl is in msec, we display this large one (loop q) in sec in form.
        $defaultInterval = $this->AMQPConnection->getDefaultSetting(MessagePublisher::VALIDATION_REMINDER_TYPE, AMQPConnection::TTL_RETRY) / 1000;
        $builder
            ->add('ttl_retry', TextType::class, [
                'label' => 'admin::workermanager:tab:Reminder: Interval in second',
                'attr' => [
                    'placeholder' => $defaultInterval
                ]
            ]);
        $builder
            ->add("boutton::appliquer", SubmitType::class, [
                'label' => "boutton::appliquer",
                'attr' => ['value' => 'save']
            ]);
/*
        $builder
            ->add("submit", ButtonType::class, [
                'label' => "start",
//                'data' => 'truc',
//                'empty_data' => 'machin',
                'attr'=>[
                    'value' => 'start',
                    'name' => 'zobi'
                ]
            ]);
*/
    }

//    public function getName()
//    {
//        return 'worker_pullAssets';
//    }
}
