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
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class PhraseaExceptionHandler implements LoggerAwareInterface
{
    private $debug;

    private $charset;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct($debug = true, $charset = null)
    {
        $this->debug = (bool) $debug;
        $this->charset = $charset ?: (ini_get('default_charset') ?: 'UTF-8');
        $this->logger = new NullLogger();
        $this->translator = new PassthroughTranslator();
    }

    /**
     * Registers the exception handler as the PHP exception handler.
     *
     * @return static
     */
    public static function register($debug = true, $charset = null)
    {
        $handler = new static($debug, $charset);

        set_exception_handler([$handler, 'handle']);

        return $handler;
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Sends a response for an uncaught exception.
     */
    public function handle(\Throwable $exception)
    {
        $this->createResponse($exception)->send();
    }

    public function createResponseBasedOnRequest(Request $request, $exception)
    {
        return $this->createResponse($exception);
    }

    /**
     * @param \Throwable|FlattenException $exception
     *
     * @return Response
     */
    public function createResponse($exception)
    {
        if (!$exception instanceof FlattenException) {
            $exception = FlattenException::createFromThrowable($exception);
        }

        return new Response(
            $this->decorate($this->getContent($exception), $this->getStylesheet($exception)),
            $exception->getStatusCode(),
            $exception->getHeaders()
        );
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

        $content = '';
        if ($this->debug) {
            $content = $this->getDebugContent($exception);
        }

        return '<div id="sf-resetcontent" class="sf-reset">'
            . '<h1><span>' . $title . '</span></h1>'
            . $content
            . '</div>';
    }

    public function getStylesheet(FlattenException $exception)
    {
        $exception->getStatusCode();

        switch ($exception->getStatusCode()) {
            case 403:
            case 404:
            case 500:
            case 503:
                $errorImg = sprintf('/assets/common/images/error-pages/%s.png', $exception->getStatusCode());
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

    private function getDebugContent(FlattenException $exception)
    {
        $exceptions = $exception->toArray();
        $total = count($exceptions);
        $content = '';

        foreach ($exceptions as $position => $e) {
            $traces = '';
            foreach ($e['trace'] as $trace) {
                $traces .= '<li>';
                if ($trace['function']) {
                    $traces .= sprintf('at %s%s%s()', $this->escape($trace['class']), $this->escape($trace['type']), $this->escape($trace['function']));
                }
                if (isset($trace['file']) && isset($trace['line'])) {
                    $traces .= sprintf(' in %s line %d', $this->escape($trace['file']), $trace['line']);
                }
                $traces .= '</li>';
            }

            $content .= sprintf(
                '<h2 class="block_exception clear_fix"><span class="exception_counter">%d/%d</span> <span class="exception_title">%s</span> <span class="exception_message">%s</span></h2>'
                . '<div class="block"><ol class="traces list_exception">%s</ol></div>',
                $total - $position,
                $total,
                $this->escape($e['class']),
                nl2br($this->escape($e['message'])),
                $traces
            );
        }

        return $content;
    }

    private function decorate($content, $css)
    {
        return <<<EOF
<!DOCTYPE html>
<html>
    <head>
        <meta charset="{$this->charset}" />
        <meta name="robots" content="noindex,nofollow" />
        <style>
            html { background: #eee; padding: 10px }
            img { border: 0; }
            #sf-resetcontent { width:970px; margin:0 auto; }
            $css
        </style>
    </head>
    <body>
        $content
    </body>
</html>
EOF;
    }

    private function escape($value)
    {
        return htmlspecialchars((string) $value, ENT_COMPAT | ENT_SUBSTITUTE, $this->charset);
    }
}
