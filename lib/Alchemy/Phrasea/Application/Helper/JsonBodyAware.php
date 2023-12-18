<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Application\Helper;

use Alchemy\Phrasea\Helper\JsonBodyHelper;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

trait JsonBodyAware
{
    /** @var JsonBodyHelper|callable|null */
    private $jsonBodyHelper;

    public function setJsonBodyHelper($helper)
    {
        if (!$helper instanceof JsonBodyHelper && !is_callable($helper)) {
            throw new \InvalidArgumentException(sprintf(
                'Expects an instance of "%s" or an callable "%s", got "%s"',
                JsonBodyHelper::class,
                is_object($helper) ? get_class($helper) : gettype($helper)
            ));
        }

        $this->jsonBodyHelper = $helper;

        return $this;
    }

    public function getJsonBodyHelper()
    {
        if ($this->jsonBodyHelper instanceof JsonBodyHelper) {
            return $this->jsonBodyHelper;
        }

        if (null === $this->jsonBodyHelper) {
            throw new \LogicException(JsonBodyHelper::class . ' locator was not set');
        }

        $instance = call_user_func($this->jsonBodyHelper);
        if (!$instance instanceof JsonBodyHelper) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                JsonBodyHelper::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->jsonBodyHelper = $instance;

        return $this->jsonBodyHelper;
    }

    /**
     * @param Request     $request
     * @param null|string $schemaUri
     * @return stdClass
     */
    public function decodeJsonBody(Request $request, $schemaUri = null, $format = JsonBodyHelper::OBJECT)
    {
        return $this->getJsonBodyHelper()->decodeJsonBody($request, $schemaUri, $format);
    }
}
