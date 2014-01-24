<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Login;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Form\Constraint\NewEmail;
use Alchemy\Phrasea\Utilities\String\Camelizer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Alchemy\Phrasea\Exception\InvalidArgumentException;

class PhraseaRegisterForm extends AbstractType
{
    private $available;
    private $params;
    private $camelizer;

    public function __construct(Application $app, array $available, array $params = [], Camelizer $camelizer = null)
    {
        $this->app = $app;
        $this->available = $available;
        $this->params = $params;
        $this->camelizer = $camelizer ?: new Camelizer();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', 'email', [
            'label'       => 'E-mail',
            'required'    => true,
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Email(),
                NewEmail::create($this->app),
            ],
        ]);

        $builder->add('password', 'repeated', [
            'type'              => 'password',
            'required'          => true,
            'invalid_message'   => 'Please provide the same passwords.',
            'first_name'        => 'password',
            'second_name'       => 'confirm',
            'first_options'     => ['label' => 'Password'],
            'second_options'    => ['label' => 'Password (confirmation)'],
            'constraints'       => [
                new Assert\NotBlank(),
                new Assert\Length(['min' => 5]),
            ],
        ]);

        if ($this->app->hasTermsOfUse()) {
            $builder->add('accept-tou', 'checkbox', [
                'label'         => 'Terms of Use',
                'mapped'        => false,
                "constraints"   => [
                    new Assert\True([
                        "message" => "Please accept the Terms and conditions in order to register."
                    ])],
            ]);
        }

        $builder->add('provider-id', 'hidden');

        $choices = [];
        $baseIds = [];

        foreach ($this->app['registration-manager']->getRegistrationInformations() as $baseInfo) {
            $dbName = $baseInfo['config']['db-name'];
            foreach ($baseInfo['config']['collections'] as $baseId => $collInfo) {
                if (false === $collInfo['can-register']) {
                    continue;
                }

                if (!isset($choices[$dbName])) {
                    $choices[$dbName] = [];
                }

                $choices[$dbName][$baseId] = \phrasea::bas_labels($baseId, $this->app);
                $baseIds[] = $baseId;
            }
        }

        if (!$this->app['conf']->get(['registry', 'registration', 'auto-select-collections'])) {
            $builder->add('collections', 'choice', [
                'choices'     => $choices,
                'multiple'    => true,
                'expanded'    => false,
                'constraints' => [
                    new Assert\Choice([
                        'choices' => $baseIds,
                        'minMessage' => 'You must select at least %s collection.',
                        'multiple' => true,
                        'min'      => 1,
                    ]),
                ],
            ]);
        }

        foreach ($this->params as $param) {
            $name = $param['name'];
            if (!preg_match('/[a-zA-Z]+/', $name)) {
                throw new InvalidArgumentException(sprintf('%s is not a valid fieldname'));
            }
            if (isset($this->available[$name])) {
                $options = array_merge($this->available[$name], ['required' => $param['required']]);
                if (!$param['required']) {
                    unset($options['constraints']);
                }
                unset($options['type']);

                $builder->add(
                    // angular does not support hyphens
                    $this->camelizer->camelize($name, '-'),
                    $this->getType($name),
                    $options
                );
            }
        }
    }

    public function getName()
    {
        return null;
    }

    private function getType($name)
    {
        return $this->available[$name]['type'];
    }
}
