<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Schema;

use JsonSchema\Validator as JsonValidator;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Plugin\Exception\JsonValidationException;

class ManifestValidator
{
    private $validator;
    private $schemaData;

    public function __construct(JsonValidator $validator, $schemaData)
    {
        if (!is_object($schemaData)) {
            throw new InvalidArgumentException('Json Schema must be an object');
        }

        $this->validator = $validator;
        $this->schemaData = $schemaData;
    }

    public function validate($data)
    {
        if (!is_object($data)) {
            throw new InvalidArgumentException('Json Schema must be an object');
        }

        $validator = clone $this->validator;
        $validator->check($data, $this->schemaData);

        if (!$validator->isValid()) {
            $errors = array();
            foreach ((array) $validator->getErrors() as $error) {
                $errors[] = ($error['property'] ? $error['property'].' : ' : '').$error['message'];
            }
            throw new JsonValidationException('Manifest file does not match the expected JSON schema', $errors);
        }

        if (!preg_match('/^[a-z0-9-_]+$/i', $data->name)) {
            throw new JsonValidationException('Does not match the expected JSON schema', array('"name" must not contains only alphanumeric caracters'));
        }

        // validate gainst versions
    }

    public static function create(JsonValidator $jsonValidator, $path)
    {
        $data = @json_decode(@file_get_contents($path));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(sprintf('Unable to read %s', $path));
        }

        return new static($jsonValidator, $data);
    }
}
