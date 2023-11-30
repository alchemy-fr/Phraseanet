<?php

namespace Alchemy\Phrasea\Command\Feedback\Report;

use Twig_Environment;

class MetadataAction extends Action implements ActionInterface
{
    /** @var string */
    private $fieldName;

    /** @var string  */
    private $method;
    /** @var string  */
    private $delimiter;

    public function __construct(Twig_Environment $twig, string $fieldName, array $action_conf)
    {
        parent::__construct($twig, $action_conf);
        $this->fieldName = $fieldName;
        $this->method = array_key_exists('method', $action_conf) ? $action_conf['method'] : '';
        $this->delimiter = array_key_exists('delimiter', $action_conf) ? $action_conf['delimiter'] : '';
    }

    public function addAction(array &$actions, array $context)
    {
        if(!array_key_exists('metadatas', $actions)) {
            $actions['metadatas'] = [];
        }
        $action = [
            "field_name" => $this->fieldName,
            "value" => trim($this->getValue($context))
        ];
        if($this->method !== '') {
            $action['method'] = $this->method;
        }
        if($this->delimiter !== '') {
            $action['delimiter'] = $this->delimiter;
        }
        $actions['metadatas'][] = $action;
    }
}
