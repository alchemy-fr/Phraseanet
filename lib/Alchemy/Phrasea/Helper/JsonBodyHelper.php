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

use JsonSchema\RefResolver;
use JsonSchema\Uri\UriRetriever;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Webmozart\Json\DecodingFailedException;
use Webmozart\Json\JsonDecoder;
use Webmozart\Json\JsonValidator;
use Webmozart\Json\ValidationFailedException;

class JsonBodyHelper
{
    /** @var JsonValidator */
    private $validator;
    /** @var JsonDecoder */
    private $decoder;
    /** @var UriRetriever */
    private $uriRetriever;
    /** @var RefResolver */
    private $refResolver;
    /** @var null|string */
    private $baseUri;

    public function __construct(
        JsonValidator $validator,
        JsonDecoder $decoder,
        UriRetriever $uriRetriever,
        RefResolver $refResolver,
        $baseUri = null
    ) {
        $this->validator = $validator;
        $this->decoder = $decoder;
        $this->uriRetriever = $uriRetriever;
        $this->refResolver = $refResolver;
        $this->baseUri = $baseUri;
    }

    /**
     * @param string $schemaUri
     * @return object
     */
    public function retrieveSchema($schemaUri)
    {
        return $this->refResolver->resolve($this->baseUri . $schemaUri);
    }

    /**
     * @param Request            $request
     * @param null|string|object $schemaUri
     * @return mixed
     */
    public function decodeJsonBody(Request $request, $schemaUri = null)
    {
        $content = $request->getContent();

        $schema = $schemaUri ? $this->retrieveSchema($schemaUri) : null;

        try {
            return $this->decoder->decode($content, $schema);
        } catch (DecodingFailedException $exception) {
            throw new UnprocessableEntityHttpException('Json request cannot be decoded', $exception);
        } catch (ValidationFailedException $exception) {
            throw new BadRequestHttpException($exception->getMessage(), $exception);
        }
    }

    /**
     * @param mixed         $data
     * @param string|object $schema
     * @return string[]
     */
    public function validateJson($data, $schema)
    {
        return $this->validator->validate($data, $schema);
    }
}
