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
 * The PdfToText object.
 *
 * This binary adapter is used to extract text from PDF with the PdfToText
 * binary provided by XPDF.
 *
 * @see https://wikipedia.org/wiki/Pdftotext
 * @license MIT
 */
class PdfToText extends AbstractBinary
{
    private $charset = 'UTF-8';
    private $pages;
    private $output_mode = '-raw';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'pdftotext';
    }

    /**
     * Sets the output encoding. If the charset is invalid, the getText method
     * will fail.
     *
     * @param  string    $charset The output charset
     * @return PdfToText
     */
    public function setOuputEncoding($charset)
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Gets the ouput encoding, default is UTF-8
     *
     * @return string
     */
    public function getOuputEncoding()
    {
        return $this->charset;
    }

    /**
     * Sets a quantity of page to extract by default
     *
     * @param integer $pages
     *
     * @return PdfToText
     */
    public function setPageQuantity($pages)
    {
        if (0 >= $pages) {
            throw new InvalidArgumentException('Page quantity must be a positive value');
        }

        $this->pages = $pages;

        return $this;
    }

    /**
     * Set the output formatting mode
     *
     * @param string $mode
     * 
     * @return PdfToText
     *
     * Valid modes are "raw" and "layout"
     */
    public function setOutputMode($mode)
    {
        switch (strtolower($mode)) {
            case 'raw':
            case '-raw':
                $this->output_mode = '-raw';
                break;
            case 'layout':
            case '-layout':
                $this->output_mode = '-layout';
                break;
            default:
                throw new InvalidArgumentException('Mode must be "raw" or "layout"');
                break;
        }

        return $this;
    }

    /**
     * Extracts the text of the current open PDF file, if not page start/end
     * provided, etxract all pages.
     *
     * @param string  $pathfile   The path to the PDF file
     * @param integer $page_start The starting page number (first is 1)
     * @param integer $page_end   The ending page number
     *
     * @return string The extracted text
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function getText($pathfile, $page_start = null, $page_end = null)
    {
        if ( ! file_exists($pathfile)) {
            throw new InvalidArgumentException(sprintf('%s is not a valid file', $pathfile));
        }

        $commands = array();

        if (null !== $page_start) {
            $commands[] = '-f';
            $commands[] = (int) $page_start;
        }
        if (null !== $page_end) {
            $commands[] = '-l';
            $commands[] = (int) $page_end;
        } elseif (null !== $this->pages) {
            $commands[] = '-l';
            $commands[] = (int) $page_start + $this->pages;
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'xpdf');

        $commands[] = $this->output_mode;
        $commands[] = '-nopgbrk';
        $commands[] = '-enc';
        $commands[] = $this->charset;
        $commands[] = '-eol';
        $commands[] = 'unix';
        $commands[] = $pathfile;
        $commands[] = $tmpFile;

        try {
            $this->command($commands);
            $ret = file_get_contents($tmpFile);
        } catch (ExecutionFailureException $e) {
            throw new RuntimeException('Unable to extract text', $e->getCode(), $e);
        } finally {
            if (is_writable($tmpFile)) {
                unlink($tmpFile);
            }
        }

        return $ret;
    }

    /**
     * Factory for PdfToText
     *
     * @param array|Configuration $configuration
     * @param LoggerInterface     $logger
     *
     * @return PdfToText
     */
    public static function create($configuration = array(), ?LoggerInterface $logger = null)
    {
        if (!$configuration instanceof ConfigurationInterface) {
            $configuration = new Configuration($configuration);
        }

        $binaries = $configuration->get('pdftotext.binaries', 'pdftotext');

        if (!$configuration->has('timeout')) {
            $configuration->set('timeout', 60);
        }

        try {
            return static::load($binaries, $logger, $configuration);
        } catch (ExecutableNotFoundException $e) {
            throw new BinaryNotFoundException('Unable to find pdftotext', $e->getCode(), $e);
        }
    }
}
