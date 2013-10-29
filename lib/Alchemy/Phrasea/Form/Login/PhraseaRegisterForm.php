<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Login;

use Alchemy\Phrasea\Application;
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

    public function __construct(Application $app, array $available, array $params = array(), Camelizer $camelizer = null)
    {
        $this->app = $app;
        $this->available = $available;
        $this->params = $params;
        $this->camelizer = $camelizer ?: new Camelizer();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', 'email', array(
            'label'       => _('E-mail'),
            'required'    => true,
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Email(),
                new \Alchemy\Phrasea\Form\Constraint\NewEmail($this->app),
            ),
        ));

        $builder->add('password', 'repeated', array(
            'type'              => 'password',
            'required'          => true,
            'invalid_message'   => _('Please provide the same passwords.'),
            'first_name'        => 'password',
            'second_name'       => 'confirm',
            'first_options'     => array('label' => _('Password')),
            'second_options'    => array('label' => _('Password (confirmation)')),
            'constraints'       => array(
                new Assert\NotBlank(),
                new Assert\Length(array('min' => 5)),
            ),
        ));

        $builder->add('accept-tou', 'checkbox', array(
            'label'         => _('Terms of Use'),
            'mapped'        => false,
            "constraints"   => array(
                new Assert\True(array(
                "message" => _("Please accept the Terms and conditions in order to register.")
            ))),
        ));

        $builder->add('provider-id', 'hidden');

        require_once $this->app['root.path'] . '/lib/classes/deprecated/inscript.api.php';
        $choices = array();
        $baseIds = array();

        foreach (\giveMeBases($this->app) as $sbas_id => $baseInsc) {
            if (($baseInsc['CollsCGU'] || $baseInsc['Colls']) && $baseInsc['inscript']) {
                if ($baseInsc['Colls']) {
                    foreach ($baseInsc['Colls'] as  $collId => $collName) {
                        $baseId = \phrasea::baseFromColl($sbas_id, $collId, $this->app);
                        $sbasName= \phrasea::sbas_names($sbas_id, $this->app);

                        if (!isset($choices[$sbasName])) {
                            $choices[$sbasName] = array();
                        }

                        $choices[$sbasName][$baseId] = \phrasea::bas_labels($baseId, $this->app);
                        $baseIds[] = $baseId;
                    }
                }

                if ($baseInsc['CollsCGU']) {
                    foreach ($baseInsc['CollsCGU'] as  $collId => $collName) {
                        $baseId = \phrasea::baseFromColl($sbas_id, $collId, $this->app);
                        $sbasName= \phrasea::sbas_names($sbas_id, $this->app);

                        if (!isset($choices[$sbasName])) {
                            $choices[$sbasName] = array();
                        }

                        $choices[$sbasName][$baseId] = \phrasea::bas_labels($baseId, $this->app);
                        $baseIds[] = $baseId;
                    }
                }
            }
        }

        var_dump($this->app['phraseanet.registry']->get('GV_autoselectDB'));
        if (!$this->app['phraseanet.registry']->get('GV_autoselectDB')) {
            $builder->add('collections', 'choice', array(
                'choices'     => $choices,
                'multiple'    => true,
                'expanded'    => false,
                'constraints' => array(
                    new Assert\Choice(array(
                        'choices' => $baseIds,
                        'minMessage' => _('You must select at least %s collection.'),
                        'multiple' => true,
                        'min'      => 1,
                    )),
                ),
            ));
        }

        foreach ($this->params as $param) {
            $name = $param['name'];
            if (!preg_match('/[a-zA-Z]+/', $name)) {
                throw new InvalidArgumentException(sprintf('%s is not a valid fieldname'));
            }
            if (isset($this->available[$name])) {
                $options = array_merge($this->available[$name], array('required' => $param['required']));
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

    private function getLabel($name)
    {
        return $this->available[$name]['label'];
    }

    private function getConstraints($name, array $constraints = array())
    {
        return isset($this->available[$name]['constraints']) ? $this->available[$name]['constraints'] : array();
    }
}
