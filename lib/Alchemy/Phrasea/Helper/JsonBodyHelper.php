<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Helper;

use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Validator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class JsonBodyHelper
{
    /**
     * Decode a JSON value as PHP object.
     */
    const OBJECT = 0;

    /**
     * Decode a JSON value as associative array.
     */
    const ASSOC_ARRAY = 1;

    /** @var SchemaStorage */
    private $schemaStorage;
    /** @var Factory */
    private $constraintFactory;
    /** @var null|string */
    private $baseUri;

    public function __construct(SchemaStorage $schemaStorage, Factory $constraintFactory, $baseUri = null)
    {
        $this->schemaStorage = $schemaStorage;
        $this->constraintFactory = $constraintFactory;
        $this->baseUri = $baseUri;
    }

    /**
     * @param string $schemaUri
     * @return object
     */
    public function retrieveSchema($schemaUri)
    {
        return $this->schemaStorage->getSchema($this->baseUri . $schemaUri);
    }

    /**
     * @param Request            $request
     * @param null|string|object $schemaUri
     * @return mixed
     */
    public function decodeJsonBody(Request $request, $schemaUri = null, $format = self::OBJECT)
    {
        if(empty($content = $request->getContent())) {
            // in case of multipart/form-data (e.g. to upload a file), the only way to send
            // also json is to pass as parameter.
            // we decide to use "body" as parameter name
            $content = $request->get('body');
        }

        $schema = is_string($schemaUri) ? $this->retrieveSchema($schemaUri) : $schemaUri;

        try {
            $data = json_decode((string) $content, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new UnprocessableEntityHttpException('Json request cannot be decoded', $exception);
        }

        if (null !== $schema) {
            $errors = $this->validateJson($data, $schema);
            if ($errors) {
                throw new BadRequestHttpException("Validation of the JSON data failed:\n" . implode("\n", $errors));
            }
        }

        return $format === self::ASSOC_ARRAY ? json_decode((string) $content, true) : $data;
    }

    /**
     * @param mixed         $data
     * @param string|object $schema
     * @return string[]
     */
    public function validateJson($data, $schema)
    {
        if (is_string($schema)) {
            $schema = $this->retrieveSchema($schema);
        }

        $validator = new Validator($this->constraintFactory);
        $validator->validate($data, $schema);

        $errors = [];
        foreach ($validator->getErrors() as $error) {
            $errors[] = ($error['property'] ? $error['property'] . ': ' : '') . $error['message'];
        }

        return $errors;
    }
}
