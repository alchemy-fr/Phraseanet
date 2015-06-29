<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Symfony\Component\Form\AbstractType;
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
            ])
            ->add('save', 'submit')
        ;
    }

    public function getName()
    {
        return 'elasticsearch_settings';
    }
}
