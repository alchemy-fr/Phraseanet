<?php

namespace Alchemy\Phrasea\Command\Feedback\Report;

use Twig_Environment;

class MetadataAction extends Action implements ActionInterface
{
    /** @var string */
    private $fieldName;

    public function __construct(Twig_Environment $twig, string $fieldName, array $action_conf)
    {
        parent::__construct($twig, $action_conf);
        $this->fieldName = $fieldName;
    }

    public function addAction(array &$actions, array $context)
    {
        if(!array_key_exists('metadatas', $actions)) {
            $actions['metadatas'] = [];
        }
        $actions['metadatas'][] = [
            "field_name" => $this->fieldName,
            "value" => trim($this->getValue($context))
        ];
    }
}
