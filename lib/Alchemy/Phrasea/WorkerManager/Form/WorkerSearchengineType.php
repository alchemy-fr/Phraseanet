<?php

namespace Alchemy\Phrasea\WorkerManager\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class WorkerSearchengineType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('host', 'text', [
                'label' => 'Elasticsearch server host',
                'constraints' => new NotBlank(),
            ])
            ->add('port', 'integer', [
                'label' => 'Elasticsearch service port',
                'constraints' => [
                    new Range(['min' => 1, 'max' => 65535]),
                    new NotBlank()
                ]
            ])
            ->add('indexName', 'text', [
                'label' => 'Elasticsearch index name',
                'constraints' => new NotBlank(),
                'attr' =>['data-class'=>'inline']
            ])
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true
        ]);
    }

    public function getName()
    {
        return 'worker_searchengine';
    }
}
