<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ElasticsearchSettingsFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('host', 'text', [
                'label' => 'ElasticSearch server host',
            ])
            ->add('port', 'integer', [
                'label' => 'ElasticSearch service port',
                'constraints' => new Range(['min' => 1, 'max' => 65535]),
            ])
            ->add('indexName', 'text', [
                'label' => 'ElasticSearch index name',
                'constraints' => new NotBlank(),
                'attr' =>['data-class'=>'inline']
            ])
            ->add('esSettingsDropIndexButton', 'button', [
                'label' => "Drop index",
                'attr' => [
                    'data-id' => 'esSettingsDropIndexButton',
                    'class' => 'btn btn-danger'
                ]
            ])
            ->add('esSettingsCreateIndexButton', 'button', [
                'label' => "Create index",
                'attr' => ['data-id' => "esSettingsCreateIndexButton",
                           'class' => 'btn btn-success'
                ]
            ])
            ->add('shards', 'integer', [
                'label' => 'Number of shards',
                'constraints' => new Range(['min' => 1]),
            ])
            ->add('replicas', 'integer', [
                'label' => 'Number of replicas',
                'constraints' => new Range(['min' => 0]),
            ])
            ->add('minScore', 'integer', [
                'label' => 'Thesaurus Min score',
                'constraints' => new Range(['min' => 0]),
            ]);

        foreach(ElasticsearchOptions::getAggregableTechnicalFields() as $k => $f) {

            if(array_key_exists('choices', $f)) {
                // choices[] : choice_key => choice_value
                $choices = $f['choices'];
            }
            else {
                $choices = [
                    "10 values" => 10,
                    "20 values" => 20,
                    "50 values" => 50,
                    "100 values" => 100,
                    "all values" => -1
                ];
            }

            // array_unshift($choices, "not aggregated");  //  always as first choice
            $choices = array_merge(["not aggregated" => 0], $choices);

            $builder
                ->add($k.'_limit', ChoiceType::class, [
                    // 'label' => $f['label'],// . ' ' . 'aggregate limit',
                    'choices_as_values' => true,
                    'choices'           => $choices,
                    'attr'              => [
                        'class' => 'aggregate'
                    ]
                ]);
        }

        $builder
            ->add('highlight', 'checkbox', [
                'label' => 'Activate highlight',
                'required' => false
            ])
//            ->add('save', 'submit', [
//                'attr' => ['class' => 'btn btn-primary']
//            ])
            ->add('esSettingFromIndex', 'button', [
                'label' => 'Get setting form index',
                'attr' => [
                    'onClick' => 'esSettingFromIndex()',
                    'class' => 'btn'
                ]
            ])
            ->add('dumpField', 'textarea', [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'dumpfield hide']
            ])
            ->add('activeTab', 'hidden');

        ;
    }

    public function getName()
    {
        return 'elasticsearch_settings';
    }
}
