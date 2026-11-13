<?php

/*
 * This file is part of PHP-XPDF.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XPDF;

use Alchemy\BinaryDriver\AbstractBinary;
use Alchemy\BinaryDriver\Exception\ExecutionFailureException;
use Alchemy\BinaryDriver\Exception\ExecutableNotFoundException;
use Alchemy\BinaryDriver\Configuration;
use Alchemy\BinaryDriver\ConfigurationInterface;
use Psr\Log\LoggerInterface;
use XPDF\Exception\InvalidArgumentException;
use XPDF\Exception\RuntimeException;
use XPDF\Exception\BinaryNotFoundException;

/**
 * The PdfInfo object.
 *
 * This binary adapter is used to extract information about pdf with the PdfInfo
 * binary provided by XPDF.
 *
 * @see https://wikipedia.org/wiki/PdfInfo
 * @license MIT
 */
class PdfInfo extends AbstractBinary
{
    private $data = array();
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdfinfo';
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Extracts the information of the current open PDF file, 
     *
     * @param string  $pathfile   The path to the PDF file
     *
     * @return array The extracted information as an associative array
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function extractInfo($pathfile)
    {
        if (! file_exists($pathfile)) {
            throw new InvalidArgumentException(sprintf('%s is not a valid file', $pathfile));
        }

        $commands = array();

        $commands[] = $pathfile;

        try {
            $output = $this->command($commands);
            $this->data = $this->parseOutputIntoData($output);
            return $this->data;
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Unable to extract info', $e->getCode(), $e);
        }
    }

    /**
     * Factory for PdfInfo
     *
     * @param array|Configuration $configuration
     * @param LoggerInterface     $logger
     *
     * @return PdfInfo
     */
    public static function create($configuration = array(), ?LoggerInterface $logger = null)
    {
        if (!$configuration instanceof ConfigurationInterface) {
            $configuration = new Configuration($configuration);
        }

        $binaries = $configuration->get('pdfinfo.binaries', 'pdfinfo');

        if (!$configuration->has('timeout')) {
            $configuration->set('timeout', 60);
        }

        try {
            return static::load($binaries, $logger, $configuration);
        } catch (ExecutableNotFoundException $e) {
            throw new BinaryNotFoundException('Unable to find pdfinfo', $e->getCode(), $e);
        }
    }
    

    /**
     * Parse output string from pdfinfo command into associative array	 
     *
     * @param String $output 
     *
     * @return array
     */
    private function parseOutputIntoData($output)
    {
        $data = array();
        $exploded = preg_split("/\\r\\n|\\r|\\n/", $output, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($exploded as $token) {
            $keyValue = preg_split("/:/", $token, 2);
            if (count($keyValue) < 2) {
                continue;
            }
            $data[$keyValue[0]] = trim($keyValue[1]);
        }
        return $data;
    }
}
