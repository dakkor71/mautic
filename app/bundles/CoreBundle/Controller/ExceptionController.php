<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Controller;

use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Mautic\CoreBundle\Helper\InputHelper;

/**
 * Class ExceptionController
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
            $code = $this->extractCode($exception);
         
            // Special handling for oauth and api urls
            if ((strpos($request->getUri(), '/oauth') !== false && strpos($request->getUri(), 'authorize') === false) || strpos($request->getUri(), '/api') !== false) {
                $dataArray = array(
                    'error' => array(
                        'message' => $exception->getMessage(),
                        'code'    => $code
                    )
                );
                if ($env == 'dev') {
                    $dataArray['trace'] = $exception->getTrace();
                }

                return new JsonResponse($dataArray, 200);
            }

            if ($request->get('prod')) {
                $layout = 'Error';
            }

            $anonymous    = $this->factory->getSecurity()->isAnonymous();
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
            $completUrl = $_SERVER['HTTP_HOST'].$url;
            
            // envoi du mail automatique au support
            $uri = $request->getUri();
            $ignoredKeywords = array(
                'apple-touch-icon',
                'dnt-policy'
            );
            
            $ignored = false;
            foreach ($ignoredKeywords as $keyword) {
                if (strpos($uri, $keyword) !== false) {
                    $ignored = true;
                    break;
                }
            }
                        
            if ($env === 'prod' && !$ignored) {
                $this->sendMailSupportAutomatique($currentUser, $completUrl, $exception);
            }
                
            // construction du mail
            $subject = $this->buildSubjectMail($code, $completUrl, false);
            $body = $this->buildBodyMailFromException($currentUser, $completUrl, $exception, false);
            $mailSupport = "support@webmecanik.com?subject=" . $subject . "&body=" . $body;
           
            /***/
            
            return $this->delegateView(
                array(
                    'viewParameters'  => array(
                        'baseTemplate'   => $baseTemplate,
                        'status_code'    => $code,
                        'status_text'    => $statusText,
                        'exception'      => $exception,
                        'logger'         => $logger,
                        'currentContent' => $currentContent,
                        'isPublicPage'   => $anonymous, 
                    	'mailSupport' => $mailSupport
                    ),
                    'contentTemplate' => $template,
                    'passthroughVars' => array(
                        'error' => array(
                            'code'      => $code,
                            'text'      => $statusText,
                            'exception' => ($env == 'dev') ? $exception->getMessage() : '',
                            'trace'     => ($env == 'dev') ? $exception->getTrace() : ''
                        ),
                        'route' => $urlParts['path']
                    )
                )
            );
        }
    }

    /**
     * @param int     $startObLevel
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
    
    /**
     * 
     */
    protected function extractCode($exception)  {
    	$code  = $exception->getStatusCode();
    	if ($code === 0) {
    		//thrown exception that didn't set a code
    		$code = 500;
    	}	
    	return $code;
    }
    
    /**
     * construct subject mail
     */
    protected function buildSubjectMail($code, $url, $auto) {
    	if($auto)
            $subject = "Automatique - ";
        else 
            $subject = "";
    		
        return $subject."Erreur code ".$code." -  ".$url;
    }
    
    protected function buildBodyMailFromException ($user, $url, $exception, $auto) {
    	$code = $this->extractCode($exception);
    	$errorMessage = $exception->getMessage();
    	$stack = $exception->getTrace();
    	
    	return $this->buildBodyMail($code, $errorMessage, $url, $stack, $user, $auto);
    }
    /**
     * construct body mail
     */
    protected function buildBodyMail($code, $errorMessage, $url, $stack, $user, $auto) {
    
    	$pile = $this->renderView('MauticCoreBundle:Exception:traces.html.php', array('traces' => $stack ));
    	if($auto) {
    		
    		$body = '<!DOCTYPE html> ';
    		$body .= '<html>';
    		$body .= '<body> ';
    		$body .= '<strong>MAIL AUTOMATIQUE</strong>  <br/> <br/> ';
    		$body .= 'User : '.$user->getName().', '.$user->getEmail().' <br/> ' ;
    		$body .= 'Type d′erreur : '.$code .' '.$errorMessage.' <br/>';
    		$body .= 'Url d′erreur : '.$url.' <br/> ';
    		$body .= 'Pile : ' . $pile. ' ';
    		$body .= '</body> ';
    		$body .= '</html> ';
    		
    	} else {
    		
    		$body = 'Votre identité : '.$user->getName().', '. $user->getEmail()." %0D%0A %0D%0A";
    		$body .= 'Ce que vous vouliez faire : '."%0D%0A %0D%0A";
    		$body .= 'Les actions que vous avez faites : '."%0D%0A %0D%0A";
    		$body .= 'Ce qui s\'est passé : '."%0D%0A %0D%0A";
    		$body .= 'Informations complémentaires : '."%0D%0A %0D%0A";
    		$body .= '*** NE PAS EFFACER CI DESSOUS - INFORMATIONS POUR LE SUPPORT ***'."%0D%0A";
    		$body .= 'URL d\'erreur : '."$url %0D%0A";
    		$body .= 'Type d\'erreur : '."$code $errorMessage %0D%0A ";
    		
    	}
    	return $body;
    }
    
    protected function sendMailSupportAutomatique ($user, $url, $exception) {
    	$emailDefault = $this->factory->getParameter('mailer_from_email');
    	
    	$code = $this->extractCode($exception);
    	$errorMessage = $exception->getMessage();
    	$stack = $exception->getTrace();
    	
    	$subjectAuto =  $this->buildSubjectMail($code, $url,  true);
    	$bodyAuto = $this->buildBodyMailFromException($user, $url, $exception, true) ;
    	
    	$subject = InputHelper::clean($subjectAuto);
    	// $body    = InputHelper::clean($bodyAuto);
    	
    	$message = \Swift_Message::newInstance()
    		->setSubject($subjectAuto)
    		->setFrom($emailDefault)
    		->setTo("support@webmecanik.com")
    		->setCharset('utf-8')
    		->setContentType("text/html")
    		->setBody($bodyAuto, 'text / html');
    	
    	$this->get('mailer')->send($message);
    }
}
