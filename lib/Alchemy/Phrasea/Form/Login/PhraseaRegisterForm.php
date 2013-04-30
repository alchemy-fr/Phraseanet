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

// write tests
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

        $builder->add('password', 'password', array(
            'label' => _('Password'),
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('min' => 5)),
            )
        ));

        $builder->add('passwordConfirm', 'password', array(
            'label' => _('Password (confirmation)'),
            'required' => false,
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array('min' => 5)),
            )
        ));

        $builder->add('accept-tou', 'checkbox', array(
            'mapped'   => false,
            "constraints" => new Assert\True(array(
                "message" => "Please accept the Terms and conditions in order to register")
            ),
        ));

        require_once($this->app['phraseanet.registry']->get('GV_RootPath') . 'lib/classes/deprecated/inscript.api.php');
        $baseIds = array();

        foreach (\giveMeBases($this->app) as $sbas_id => $baseInsc) {
            if (($baseInsc['CollsCGU'] || $baseInsc['Colls']) && $baseInsc['inscript']) {
                if ($baseInsc['Colls']) {
                    foreach($baseInsc['Colls'] as  $collId => $collName) {
                        $baseIds[\phrasea::baseFromColl($sbas_id, $collId, $this->app)] = $collName;
                    }
                }
                if ($baseInsc['CollsCGU']) {
                    foreach($baseInsc['CollsCGU'] as  $collId => $collName) {
                        $baseIds[\phrasea::baseFromColl($sbas_id, $collId, $this->app)] = $collName;
                    }
                }
            }
        }

        $builder->add('collections', 'choice', array(
            'choices'    => $baseIds,
            'multiple'   => true,
            'expanded'   => true,
        ));

        foreach ($this->params as $param) {
            $name = $param['name'];
            if (isset($this->available[$name])) {
                $builder->add(
                    $this->camelizer->camelize($name, '-'),
                    $this->getType($name),
                    array(
                        'label'       => $this->getLabel($name),
                        'required'    => $param['required'],
                        'constraints' => $this->getConstraints($name),//, $param['constraints']),
                    )
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
