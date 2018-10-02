<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin\Schema;

use Alchemy\Phrasea\Application;
use JsonSchema\Validator as JsonValidator;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Plugin\Exception\JsonValidationException;
use Alchemy\Phrasea\Core\Version as PhraseaVersion;
use vierbergenlars\SemVer\version;

class ManifestValidator
{
    private $validator;
    private $version;
    private $schemaData;

    public function __construct(JsonValidator $validator, $schemaData, PhraseaVersion $version)
    {
        if (!is_object($schemaData)) {
            throw new InvalidArgumentException('Json Schema must be an object');
        }

        $this->validator = $validator;
        $this->version = $version;
        $this->schemaData = $schemaData;
    }

    public function validate($data)
    {
        if (!is_object($data)) {
            throw new InvalidArgumentException('Json Schema must be an object');
        }

        $this->validator->reset();
        $this->validator->check($data, $this->schemaData);

        if (!$this->validator->isValid()) {
            $errors = [];
            foreach ((array) $this->validator->getErrors() as $error) {
                $errors[] = ($error['property'] ? $error['property'].' : ' : '').$error['message'];
            }
            throw new JsonValidationException('Manifest file does not match the expected JSON schema', $errors);
        }

        if (!preg_match('/^[a-z0-9-_]+$/', $data->name)) {
            throw new JsonValidationException('Does not match the expected JSON schema', ['"name" must not contains only alphanumeric caracters']);
        }

        if (isset($data->{'minimum-phraseanet-version'})) {
            if (version::lt($this->version->getNumber(), $data->{'minimum-phraseanet-version'})) {
                throw new JsonValidationException(sprintf(
                    'Version incompatibility : Minimum Phraseanet version required is %s, current version is %s',
                    $data->{'minimum-phraseanet-version'},
                    $this->version->getNumber()
                ));
            }
        }

        if (isset($data->{'maximum-phraseanet-version'})) {
            if (version::gte($this->version->getNumber(), $data->{'maximum-phraseanet-version'})) {
                throw new JsonValidationException(sprintf(
                    'Version incompatibility : Maximum Phraseanet version required is %s, current version is %s',
                    $data->{'maximum-phraseanet-version'},
                    $this->version->getNumber()
                ));
            }
        }
    }

    public static function create(Application $app)
    {
        $data = @json_decode(@file_get_contents($app['plugins.schema']));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidArgumentException(sprintf('Unable to read %s', $app['plugins.schema']));
        }

        return new static($app['plugins.json-validator'], $data, $app['phraseanet.version']);
    }
}
