<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller;

use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class MediaAccessorController extends Controller
{
    /** @var array|\ArrayAccess */
    private $keyStorage = [];
    /** @var array */
    private $allowedAlgorithms = [];

    /**
     * @param array|\ArrayAccess $keyStorage
     * @return $this
     */
    public function setKeyStorage($keyStorage)
    {
        if (!is_array($keyStorage) && !$keyStorage instanceof \ArrayAccess) {
            throw new \InvalidArgumentException(sprintf(
                'expects $keyStorage to be an array or an instance of ArrayAccess, got %s',
                is_object($keyStorage) ? get_class($keyStorage) : gettype($keyStorage)
            ));
        }
        $this->keyStorage = $keyStorage;

        return $this;
    }

    /**
     * @param array $allowedAlgorithms
     * @return $this
     */
    public function setAllowedAlgorithms(array $allowedAlgorithms)
    {
        $this->allowedAlgorithms = $allowedAlgorithms;

        return $this;
    }

    public function showAction(Request $request, $token)
    {
        list($sbas_id, $record_id, $subdef) = $this->validateToken($token);

        try {
            $databox = $this->findDataboxById($sbas_id);
            $record = $databox->get_record($record_id);
            $subDefinition = $record->get_subdef($subdef);
            $permalink = $subDefinition->get_permalink();
        } catch (\Exception $exception) {
            throw new NotFoundHttpException('Media was not found', $exception);
        }

        $subRequest = Request::create(
            (string) $permalink->get_url(),
            'GET',
            [],
            $request->cookies->all(),
            [],
            $request->server->all()
        );

        if ($request->query->has('download')) {
            $subRequest->query->set('download', $request->query->get('download'));
        }

        if ($request->query->has('filename')) {
            $subRequest->query->set('filename', $request->query->get('filename'));
        }

        $response = $this->app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
        // Remove Caption link header as it contains permalink token.
        $response->headers->remove('link');

        return $response;
    }

    /**
     * @param string $token
     * @return object
     */
    public function decodeToken($token)
    {
        try {
            return JWT::decode($token, $this->keyStorage, $this->allowedAlgorithms);
        } catch (\UnexpectedValueException $exception) {
            throw new NotFoundHttpException('Resource not found', $exception);
        } catch (\Exception $exception) {
            throw new BadRequestHttpException('Invalid token', $exception);
        }
    }

    /**
     * Validate token and returns triplet containing sbas_id, record_id and subdef.
     *
     * @param string|object $token
     * @return array
     */
    public function validateToken($token)
    {
        if (is_string($token)) {
            $token = $this->decodeToken($token);
        }

        if (!isset($token->sdef) || !is_array($token->sdef) || count($token->sdef) !== 3) {
            throw new BadRequestHttpException('sdef should be a sub-definition identifier.');
        }
        list ($sbas_id, $record_id, $subdef) = $token->sdef;

        return array($sbas_id, $record_id, $subdef);
    }
}
