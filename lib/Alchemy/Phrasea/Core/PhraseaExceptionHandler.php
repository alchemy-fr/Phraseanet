<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core;

use Alchemy\Phrasea\Utilities\PassthroughTranslator;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Debug\ExceptionHandler as SymfonyExceptionHandler;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

class PhraseaExceptionHandler extends SymfonyExceptionHandler implements LoggerAwareInterface
{

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var NullLogger
     */
    private $logger;

    public function __construct()
    {
        $this->logger = new NullLogger();
        $this->translator = new PassthroughTranslator();
    }

    /**
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function createResponseBasedOnRequest(Request $request, $exception)
    {
        return parent::createResponse($exception);
    }

    public function getContent(FlattenException $exception)
    {
        if ($exception->getStatusCode() == '500') {
            $this->logger->error($exception->getMessage(), [
                'code' => $exception->getCode(),
                'trace' => $exception->getTrace()
            ]);
        }

        switch (true) {
            case 404 === $exception->getStatusCode():
                $title = $this->translator->trans('Sorry, the page you are looking for could not be found.');
                break;
            case 403 === $exception->getStatusCode():
                $title = $this->translator->trans('Sorry, you do have access to the page you are looking for.');
                break;
            case 500 === $exception->getStatusCode():
                $title = $this->translator->trans('Whoops, looks like something went wrong.');
                break;
            case 503 === $exception->getStatusCode():
                $title = $this->translator->trans('Sorry, site is currently undergoing maintenance, come back soon.');
                break;
            case isset(Response::$statusTexts[$exception->getStatusCode()]):
                $title = $exception->getStatusCode() . ' : ' . Response::$statusTexts[$exception->getStatusCode()];
                break;
            default:
                $title = $this->translator->trans('Whoops, looks like something went wrong.');
        }

        $content = parent::getContent($exception);
        $start = strpos($content, '</h1>');

        $content = '<div id="sf-resetcontent" class="sf-reset">'
            . '<h1><span>' . $title . '</span></h1>'
            . substr($content, $start + 5);

        return $content;
    }

    public function getStylesheet(FlattenException $exception)
    {
        $exception->getStatusCode();

        switch ($exception->getStatusCode()) {
            case 403:
                $errorImg = '/assets/common/images/error-pages/403.png';
                break;
            case 404:
                $errorImg = '/assets/common/images/error-pages/404.png';
                break;
            case 500:
                $errorImg = '/assets/common/images/error-pages/500.png';
                break;
            case 503:
                $errorImg = '/assets/common/images/error-pages/503.png';
                break;
            default:
                $errorImg = '/assets/common/images/error-pages/error.png';
                break;
        }

        return <<<EOF
            html {
                background-image:url("/assets/common/images/error-pages/background.png");
                background-repeat:repeat;
                padding-top:0px;
            }
            body {
                background-image:url("$errorImg");
                background-repeat:no-repeat;
                background-position:top center;
            }
            .sf-reset { font: 11px Arial, Verdana, sans-serif; color: #333 }
            .sf-reset .clear { clear:both; height:0; font-size:0; line-height:0; }
            .sf-reset .clear_fix:after { display:block; height:0; clear:both; visibility:hidden; }
            .sf-reset .clear_fix { display:inline-block; }
            .sf-reset * html .clear_fix { height:1%; }
            .sf-reset .clear_fix { display:block; }
            .sf-reset, .sf-reset .block { margin: auto }
            .sf-reset abbr { border-bottom: 1px dotted #000; cursor: help; }
            .sf-reset p { font-size:14px; line-height:20px; color:#868686; padding-bottom:20px }
            .sf-reset strong { font-weight:bold; }
            .sf-reset a { color:#6c6159; }
            .sf-reset a img { border:none; }
            .sf-reset a:hover { text-decoration:underline; }
            .sf-reset em { font-style:italic; }
            .sf-reset h2 { font: 20px Arial, Verdana, sans-serif; color: #3C3C3B; }
            .sf-reset h2 span {
                background-color: #fff;
                color: #333;
                padding: 6px;
                float: left;
                margin-right: 10px;
                color: #ED7060;
                border-radius: 5px;
                -webkit-border-radius: 5px;
                -moz-border-radius: 5px;
            }
            .sf-reset .traces li { font-size:15px; padding: 2px 4px; list-style-type:decimal; margin-left:20px; margin-top:15px; }
            .sf-reset .block { background-color:#FFFFFF; padding:10px 28px; margin-bottom:20px;
                border-bottom:1px solid #ccc;
                border-right:1px solid #ccc;
                border-left:1px solid #ccc;
            }
            .sf-reset .block_exception {
                background-color:#ddd;
                color: #333;
                padding:20px;
                -webkit-border-top-left-radius: 16px;
                -webkit-border-top-right-radius: 16px;
                -moz-border-radius-topleft: 16px;
                -moz-border-radius-topright: 16px;
                border-top-left-radius: 16px;
                border-top-right-radius: 16px;
                border-top:1px solid #ccc;
                border-right:1px solid #ccc;
                border-left:1px solid #ccc;
                overflow: hidden;
                word-wrap: break-word;
                background-color: #719AAF;
            }
            .sf-reset li a { background:none; color:#868686; text-decoration:none; }
            .sf-reset li a:hover { background:none; color:#313131; text-decoration:underline; }
            .sf-reset ol { padding: 10px 0; }
            .sf-reset h1 {
                height:510px;
            }
            .sf-reset h1 span { color:#646363; display:inline-block; margin-top:430px; margin-left:190px; font-size:28px; }
EOF;
    }
}
