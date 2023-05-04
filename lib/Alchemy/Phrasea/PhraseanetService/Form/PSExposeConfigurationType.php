<?php

namespace Alchemy\Phrasea\PhraseanetService\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class PSExposeConfigurationType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('activated', CheckboxType::class, [
                'label'    => 'admin:phrasea-service-setting:tab:expose:: activate Phraseanet-service expose',
                'required' => false,
                'attr'      => [
                    'class' => 'activate-expose',
                ]
            ])
            ->add('exposes', CollectionType::class, [
                'label'         => false,
                'entry_type'    => PSExposeConnectionType::class,
                'prototype'     => true,
                'allow_add'     => true,
                'allow_delete'  => true,
            ])
            ->setDataMapper($this)
        ;
    }

    /**
     * @inheritDoc
     */
    public function mapDataToForms($data, $forms)
    {
        // there is no data yet, so nothing to prepopulate
        if ($data === null) {
            return;
        }

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        if (isset($data['exposes'] )) {
            foreach ($data['exposes'] as $key => $config) {
                $data['exposes'][$key]['expose_name'] = $key;
            }

            $forms['exposes']->setData(array_values($data['exposes']));
        }

        $forms['activated']->setData($data['activated']);
    }

    /**
     *  Data structure like this
     *
     *   expose-service:
     *       activated: true
     *       exposes:
     *              expose_test:
     *                  activate_expose: true
     *                  connection_kind: password
     *                  expose_front_uri: 'localhost:8080'
     *                  expose_base_uri: 'localhost:8082'
     *                  client_secret: secret
     *                  client_id: id
     *
     * @inheritDoc
     */
    public function mapFormsToData($forms, &$data)
    {

        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $data = null;

        $data['activated'] = $forms['activated']->getData();

        /** @var FormInterface[] $exposeConfigForms */
        $exposeConfigForms = iterator_to_array($forms['exposes']);

        foreach ($exposeConfigForms as $exposeConfigForm) {
            $config = $exposeConfigForm->getData();
            $exposeName = $config['expose_name'];
            unset($config['expose_name']);

            $data['exposes'][$exposeName] = $config;
        }
    }

    public function getName()
    {
        return 'ps_expose_configuration';
    }
}
