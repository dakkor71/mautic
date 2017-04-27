<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * Class ExceptionController.
 */
class ExceptionController extends CommonController
{
    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $class = $exception->getClass();

        //ignore authentication exceptions
        if (strpos($class, 'Authentication') === false) {
            $env            = $this->factory->getEnvironment();
            $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
            $layout         = $env == 'prod' ? 'Error' : 'Exception';
            $code           = $exception->getStatusCode();
            if ($code === 0) {
                //thrown exception that didn't set a code
                $code = 500;
            }

            // Special handling for oauth and api urls
            if (
                (strpos($request->getUri(), '/oauth') !== false && strpos($request->getUri(), 'authorize') === false) ||
                strpos($request->getUri(), '/api') !== false ||
                (!defined('MAUTIC_AJAX_VIEW') && strpos($request->server->get('HTTP_ACCEPT', ''), 'application/json') !== false)
            ) {
                $dataArray = [
                    'errors' => [
                        [
                            'message' => $exception->getMessage(),
                            'code'    => $code,
                            'type'    => null,
                        ],
                    ],
                    // @deprecated 2.6.0 to be removed in 3.0
                    'error' => [
                        'message' => $exception->getMessage().' (`error` is deprecated as of 2.6.0 and will be removed in 3.0. Use the `errors` array instead.)',
                        'code'    => $code,
                    ],
                ];
                if ($env == 'dev') {
                    $dataArray['trace'] = $exception->getTrace();
                }

                return new JsonResponse($dataArray, 200);
            }

            if ($request->get('prod')) {
                $layout = 'Error';
            }

            $anonymous    = $this->get('mautic.security')->isAnonymous();
            $baseTemplate = 'MauticCoreBundle:Default:slim.html.php';
            if ($anonymous) {
                if ($templatePage = $this->factory->getTheme()->getErrorPageTemplate($code)) {
                    $baseTemplate = $templatePage;
                }
            }

            $template   = "MauticCoreBundle:{$layout}:{$code}.html.php";
            $templating = $this->factory->getTemplating();
            if (!$templating->exists($template)) {
                $template = "MauticCoreBundle:{$layout}:base.html.php";
            }

            $statusText = isset(Response::$statusTexts[$code]) ? Response::$statusTexts[$code] : '';

            $url      = $request->getRequestUri();
            $urlParts = parse_url($url);

            /***/
            $currentUser = $this->factory->getUser();
            $completUrl  = $_SERVER['HTTP_HOST'].$url;

            // construction du mail pour "report an issue"
            $subject = $this->buildSubjectMail($code);
            $body    = $this->buildBodyMailFromException($currentUser, $completUrl, $exception);

            $mailDest    = $this->coreParametersHelper->getParameter('mail_error_support_manual_report', 'support@webmecanik.com');
            $mailSupport = $mailDest.'?subject='.$subject.'&body='.$body;
            /***/

            return $this->delegateView(
                [
                    'viewParameters' => [
                        'baseTemplate'   => $baseTemplate,
                        'status_code'    => $code,
                        'status_text'    => $statusText,
                        'exception'      => $exception,
                        'logger'         => $logger,
                        'currentContent' => $currentContent,
                        'isPublicPage'   => $anonymous,
                        'mailSupport'    => $mailSupport,
                    ],
                    'contentTemplate' => $template,
                    'passthroughVars' => [
                        'error' => [
                            'code'      => $code,
                            'text'      => $statusText,
                            'exception' => ($env == 'dev') ? $exception->getMessage() : '',
                            'trace'     => ($env == 'dev') ? $exception->getTrace() : '',
                        ],
                        'route' => $urlParts['path'],
                    ],
                    'responseCode' => $code,
                ]
            );
        }
    }

    /**
     * @param int $startObLevel
     *
     * @return string
     */
    protected function getAndCleanOutputBuffering($startObLevel)
    {
        if (ob_get_level() <= $startObLevel) {
            return '';
        }

        Response::closeOutputBuffers($startObLevel + 1, true);

        return ob_get_clean();
    }

    protected function extractCode($exception)
    {
        $code = $exception->getStatusCode();
        if ($code === 0) {
            //thrown exception that didn't set a code
            $code = 500;
        }

        return $code;
    }

    /**
     * construct subject mail.
     */
    protected function buildSubjectMail($code)
    {
        $subject = 'Demande de support - Code : '.$code;
        return $subject;
    }

    protected function buildBodyMailFromException($user, $url, $exception)
    {
        $code         = $this->extractCode($exception);
        $errorMessage = $exception->getMessage();
        $stack        = $exception->getTrace();

        return $this->buildBodyMail($code, $errorMessage, $url, $stack, $user);
    }
    /**
     * construct body mail.
     */
    protected function buildBodyMail($code, $errorMessage, $url, $stack, $user)
    {
        $pile = $this->renderView('MauticCoreBundle:Exception:traces.html.php', ['traces' => $stack]);
        $body = 'Votre identité : '.$user->getName().', '.$user->getEmail().' %0D%0A %0D%0A';
        $body .= 'Ce que vous vouliez faire : '.'%0D%0A %0D%0A';
        $body .= 'Les actions que vous avez faites : '.'%0D%0A %0D%0A';
        $body .= 'Ce qui s\'est passé : '.'%0D%0A %0D%0A';
        $body .= 'Informations complèmentaires : '.'%0D%0A %0D%0A';
        $body .= '*** NE PAS EFFACER CI DESSOUS - INFORMATIONS POUR LE SUPPORT ***'.'%0D%0A';
        $body .= 'URL d\'erreur : '."$url %0D%0A";
        $body .= 'Type d\'erreur : '."$code $errorMessage %0D%0A ";

        return $body;
    }
}
