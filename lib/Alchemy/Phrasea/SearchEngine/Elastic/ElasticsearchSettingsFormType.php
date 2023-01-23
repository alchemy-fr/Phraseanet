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

use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure;
use JMS\TranslationBundle\Annotation\Ignore;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class ElasticsearchSettingsFormType extends AbstractType
{
    /** @var GlobalStructure  */
    private $globalStructure;

    /** @var ElasticsearchOptions  */
    private $esSettings;

    private $translator;

    public function  __construct(GlobalStructure $g, ElasticsearchOptions $settings, TranslatorInterface $translator)
    {
        $this->globalStructure = $g;
        $this->esSettings = $settings;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('host', TextType::class, [
                'label' => 'ElasticSearch server host',
            ])
            ->add('port', IntegerType::class, [
                'label' => 'ElasticSearch service port',
                'constraints' => new Range(['min' => 1, 'max' => 65535]),
            ])
            ->add('indexName', TextType::class, [
                'label' => 'ElasticSearch index name',
                'constraints' => new NotBlank(),
                'attr' =>['data-class'=>'inline']
            ])
            ->add('esSettingsDropIndexButton', ButtonType::class, [
                'label' => "Drop index",
                'attr' => [
                    'data-id' => 'esSettingsDropIndexButton',
                    'class' => 'btn btn-danger'
                ]
            ])
            ->add('esSettingsCreateIndexButton', ButtonType::class, [
                'label' => "Create index",
                'attr' => ['data-id' => "esSettingsCreateIndexButton",
                           'class' => 'btn btn-success'
                ]
            ])
            ->add('shards', IntegerType::class, [
                'label' => 'Number of shards',
                'constraints' => new Range(['min' => 1]),
            ])
            ->add('replicas', IntegerType::class, [
                'label' => 'Number of replicas',
                'constraints' => new Range(['min' => 0]),
            ])
            ->add('minScore', IntegerType::class, [
                'label' => 'Thesaurus Min score',
                'constraints' => new Range(['min' => 0]),
            ])
            ->add('highlight', CheckboxType::class, [
                'label' => 'Activate highlight',
                'required' => false
            ])
            //                ->add('save', 'submit', [
            //                    'attr' => ['class' => 'btn btn-primary']
            //                ])
            ->add('esSettingFromIndex', ButtonType::class, [
                'label' => 'Get setting form index',
                'attr' => [
                    'onClick' => 'esSettingFromIndex()',
                    'class' => 'btn'
                ]
            ])
            ->add('dumpField', TextareaType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'attr' => ['class' => 'dumpfield hide']
            ])
            ->add('activeTab', HiddenType::class);

        // keep aggregates in configuration order with this intermediate array
        $aggs = [];

        // helper fct to add aggregate to a tmp list
        $addAgg = function($k, $label, $help, $disabled=false, $choices=null) use (&$aggs) {
            if(!$choices) {
                $choices = [
                    "10 values"      => 10,
                    "50 values"      => 50,
                    "100 values"     => 100,
                    "all values"     => -1
                ];
            }
            $choices = array_merge(["not aggregated" => 0], $choices);  //  add this option always as first choice
            $aggs[$k] = [   // default value will be replaced by hardcoded tech fields & all databoxes fields
                'label'              => $label,
                'choices_as_values'  => true,
                'choices' => $choices,
                'attr'               => [
                    'class' => 'aggregate'
                ],
                'disabled'           => $disabled,
                'help_message'       => /** @Ignore */ $help,     // todo : not displayed ?
                'translation_domain' => false
            ];
        };

        // all fields fron conf
        foreach($this->esSettings->getAggregableFields() as $k=>$f) {
            // default value will be replaced by hardcoded tech fields & all databoxes fields
            $addAgg($k, "/?\\ " . $k, $this->translator->trans("admin:searchengine:aggregates:This field does not exists in current databoxes."), true);
        }

        // add or replace hardcoded tech fields
        foreach(ElasticsearchOptions::getAggregableTechnicalFields($this->translator) as $k => $f) {
            $choices = array_key_exists('choices', $f) ? $f['choices'] : null;   // a tech-field can publish it's own choices
            $help = null;
            $label = '#' . $k;
            if(!array_key_exists($k, $aggs)) {
                $label = "/!\\ " . $label;
                $help = $this->translator->trans("admin:searchengine:aggregates:New field, please confirm setting.");
            }
            $addAgg($k, $label, $help, false, $choices);
        }

        // add or replace all databoxes fields (nb: new db field - unknown in conf - will be a the end)
        foreach($this->globalStructure->getAllFields() as $field) {
            $k = $label = $field->getName();
            $help = null;
            if(!array_key_exists($field->getName(), $aggs)) {
                $label = "/!\\ " . $label;
                $help = $this->translator->trans("admin:searchengine:aggregates:New field, please confirm setting.");
            }
            $addAgg($k, $label, $help);     // default choices
        }

        // populate aggs to form
        foreach($aggs as $k=>$agg) {
            $builder->add('facets:' . $k . ':limit', ChoiceType::class, $agg);
        }

    }

    public function getName()
    {
        return 'elasticsearch_settings';
    }
}
