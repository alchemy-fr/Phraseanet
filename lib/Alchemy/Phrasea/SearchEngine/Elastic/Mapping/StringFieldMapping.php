<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Mapping;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;

class StringFieldMapping extends FieldMapping
{
    /**
     * @var bool
     */
    private $enableAnalysis = true;

    /**
     * @var string|null
     */
    private $analyzer = null;

    /**
     * @var string|null
     */
    private $searchAnalyzer = null;

    /**
     * @param string $analyzer
     * @param string|null $type
     */
    public function setAnalyzer($analyzer, $type = null)
    {
        /**
         * @todo Split into separate setters
         */
        switch ($type) {
            case null:
                $this->analyzer = $analyzer;
                $this->searchAnalyzer = null;

                break;
            case 'indexing':
                $this->analyzer = $analyzer;

                break;
            case 'searching':
                $this->searchAnalyzer = $analyzer;

                break;
            default:
                throw new \LogicException(sprintf('Invalid analyzer type "%s".', $type));
        }
    }

    public function disableAnalysis()
    {
        $this->enableAnalysis = false;
    }

    public function enableAnalysis()
    {
        $this->enableAnalysis = true;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $configuration = [];

        if ($this->analyzer) {
            $configuration['analyzer'] = $this->analyzer;
        }

        if ($this->searchAnalyzer) {
            $configuration['search_analyzer'] = $this->searchAnalyzer;
        }

        if (! $this->enableAnalysis) {
            $configuration['index'] = 'not_analyzed';
        }

        return $this->buildArray($configuration);
    }
}
