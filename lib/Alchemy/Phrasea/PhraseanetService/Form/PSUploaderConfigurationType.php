<?php

namespace Alchemy\Phrasea\PhraseanetService\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class PSUploaderConfigurationType extends AbstractType implements DataMapperInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('act', HiddenType::class, [
                'attr' => [
                    'class' => 'act'
                ]
            ]);

        $builder
            ->add('push_verify_ssl', CheckboxType::class, [
                'label'    => 'admin:phrasea-service-setting:tab:uploader:: push verify_ssl',
                'required' => false,
            ])
            ->add('pullInterval', IntegerType::class, [
                'label' => 'admin:phrasea-service-setting:tab:uploader:: Fetching interval in second',
            ])
            ->add('pulled_target', CollectionType::class, [
                'label'         => false,
                'entry_type'    => PSUploaderPullType::class,
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

        if (isset($data['pulled_target'] )) {
            foreach ($data['pulled_target'] as $key => $config) {
                $data['pulled_target'][$key]['target_name'] = $key;
            }

            $forms['pulled_target']->setData(array_values($data['pulled_target']));
        }

        if (isset($data['push_verify_ssl'])) {
            $forms['push_verify_ssl']->setData($data['push_verify_ssl']);
        }

        if (isset($data['pullInterval'])) {
            $forms['pullInterval']->setData($data['pullInterval']);
        }
    }

    /**
     *  Data structure like this
     *
     *   uploader-service:
     *       push_verify_ssl: true
     *       pullInterval: 60
     *       pulled_target:
     *              target_name1:
     *                  pullmodeUri: "pull mode url"
     *                  client_secret: secret
     *                  client_id: id
     *                  verify_ssl: true
     *
     * @inheritDoc
     */
    public function mapFormsToData($forms, &$data)
    {
        /** @var FormInterface[] $forms */
        $forms = iterator_to_array($forms);

        $data = null;

        $data['push_verify_ssl'] = $forms['push_verify_ssl']->getData();
        $data['pullInterval'] = $forms['pullInterval']->getData();
        $data['act'] = $forms['act']->getData();

        /** @var FormInterface[] $exposeConfigForms */
        $uploaderConfigForms = iterator_to_array($forms['pulled_target']);

        foreach ($uploaderConfigForms as $uploaderConfigForm) {
            $config = $uploaderConfigForm->getData();
            $targetName = $config['target_name'];
            unset($config['target_name']);

            $data['pulled_target'][$targetName] = $config;
        }
    }

    public function getName()
    {
        return 'ps_uploader_configuration';
    }
}
