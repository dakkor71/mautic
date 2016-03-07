<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\Helper\MailHelper;
use Symfony\Component\HttpFoundation\Request;
use Mailjet\Resources;

/**
 * Class MailjetlTransport
 */
class MailjetTransport extends AbstractTokenHttpTransport implements InterfaceCallbackTransport
{

    /**
     * {@inheritdoc}
     */
    protected function getPayload()
    {
    	$Payload = [
				    'FromEmail' => "contact@webmecanik.com",
				    'FromName' => "Test API mailjet!",
				    'Subject' => "Test API mailjet!",
				    'Text-part' => "Dear passenger, welcome to Mailjet! May the delivery force be with you!",
				    'Html-part' => "<h3>Dear passenger, welcome to Mailjet!</h3><br />May the delivery force be with you!",
				    'Recipients' => [
				        [
				            'Email' => "david.c@algofly.fr"
				        ],
			    		[
			    			'Email' => "dco@webmecsdfanik.com"
			    		]
				    ]
				];
    	return $Payload;
    	
    	
        $metadata     = $this->getMetadata();
        $mauticTokens = $mandrillMergeVars = $mandrillMergePlaceholders = array();

        // Mandrill uses *|PLACEHOLDER|* for tokens so Mautic's need to be replaced
        if (!empty($metadata)) {
            $metadataSet  = reset($metadata);
            $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : array();
            $mauticTokens = array_keys($tokens);

            $mandrillMergeVars = $mandrillMergePlaceholders = array();
            foreach ($mauticTokens as $token) {
                $mandrillMergeVars[$token]         = strtoupper(preg_replace("/[^a-z0-9]+/i", "", $token));
                $mandrillMergePlaceholders[$token] = '*|'.$mandrillMergeVars[$token].'|*';
            }
        }

        $message = $this->messageToArray($mauticTokens, $mandrillMergePlaceholders, true);

        // Not used ATM
        unset($message['headers']);

        $message['from_email'] = $message['from']['email'];
        $message['from_name']  = $message['from']['name'];
        unset($message['from']);

        if (!empty($metadata)) {
            // Mandrill will only send a single email to cc and bcc of the first set of tokens
            // so we have to manually set them as to addresses

            // Problem is that it's not easy to know what email is sent so will tack it at the top
            $insertCcEmailHeader = true;

            $message['html'] = '*|HTMLCCEMAILHEADER|*'.$message['html'];
            if (!empty($message['text'])) {
                $message['text'] = '*|TEXTCCEMAILHEADER|*'.$message['text'];
            }

            // Do not expose all the emails in the if using metadata
            $message['preserve_recipients'] = false;

            $bcc = $message['recipients']['bcc'];
            $cc  = $message['recipients']['cc'];

            // Unset the cc and bcc as they will need to be sent as To with each set of tokens
            unset($message['recipients']['bcc'], $message['recipients']['cc']);
        }

        // Generate the recipients
        $recipients = $rcptMergeVars = $rcptMetadata = array();

        $translator = $this->factory->getTranslator();

        foreach ($message['recipients'] as $type => $typeRecipients) {
            foreach ($typeRecipients as $rcpt) {
                $rcpt['type'] = $type;
                $recipients[] = $rcpt;

                if ($type == 'to' && isset($metadata[$rcpt['email']])) {
                    if (!empty($metadata[$rcpt['email']]['tokens'])) {
                        $mergeVars = array(
                            'rcpt' => $rcpt['email'],
                            'vars' => array()
                        );

                        // This must not be included for CC and BCCs
                        $trackingPixelToken = array();

                        foreach ($metadata[$rcpt['email']]['tokens'] as $token => $value) {
                            if ($token == '{tracking_pixel}') {
                                $trackingPixelToken = array(
                                    array(
                                        'name'    => $mandrillMergeVars[$token],
                                        'content' => $value
                                    )
                                );

                                continue;
                            }

                            $mergeVars['vars'][] = array(
                                'name'    => $mandrillMergeVars[$token],
                                'content' => $value
                            );
                        }

                        if (!empty($insertCcEmailHeader)) {
                            // Make a copy before inserted the blank tokens
                            $ccMergeVars       = $mergeVars;
                            $mergeVars['vars'] = array_merge(
                                $mergeVars['vars'],
                                $trackingPixelToken,
                                array(
                                    array(
                                        'name'    => 'HTMLCCEMAILHEADER',
                                        'content' => ''
                                    ),
                                    array(
                                        'name'    => 'TEXTCCEMAILHEADER',
                                        'content' => ''
                                    )
                                )
                            );
                        } else {
                            // Just merge the tracking pixel tokens
                            $mergeVars['vars'] = array_merge($mergeVars['vars'], $trackingPixelToken);
                        }

                        // Add the vars
                        $rcptMergeVars[] = $mergeVars;

                        // Special handling of CC and BCC with tokens
                        if (!empty($cc) || !empty($bcc)) {
                            $ccMergeVars['vars'] = array_merge(
                                $ccMergeVars['vars'],
                                array(
                                    array(
                                        'name'    => 'HTMLCCEMAILHEADER',
                                        'content' => $translator->trans('mautic.core.email.cc.copy',
                                            array(
                                                '%email%' => $rcpt['email']
                                            )
                                        ) . "<br /><br />"
                                    ),
                                    array(
                                        'name'    => 'TEXTCCEMAILHEADER',
                                        'content' => $translator->trans('mautic.core.email.cc.copy',
                                            array(
                                                '%email%' => $rcpt['email']
                                            )
                                        ) . "\n\n"
                                    ),
                                    array(
                                        'name'    => 'TRACKINGPIXEL',
                                        'content' => MailHelper::getBlankPixel()
                                    )
                                )
                            );

                            // If CC and BCC, remove the ct from URLs to prevent false lead tracking
                            foreach ($ccMergeVars['vars'] as &$var) {
                                if (strpos($var['content'], 'http') !== false && $ctPos = strpos($var['content'], 'ct=') !== false) {
                                    // URL so make sure a ct query is not part of it
                                    $var['content'] = substr($var['content'], 0, $ctPos);
                                }
                            }

                            // Send same tokens to each CC
                            if (!empty($cc)) {
                                foreach ($cc as $ccRcpt) {
                                    $recipients[]        = $ccRcpt;
                                    $ccMergeVars['rcpt'] = $ccRcpt['email'];
                                    $rcptMergeVars[]     = $ccMergeVars;
                                }
                            }

                            // And same to BCC
                            if (!empty($bcc)) {
                                foreach ($bcc as $ccRcpt) {
                                    $recipients[]        = $ccRcpt;
                                    $ccMergeVars['rcpt'] = $ccRcpt['email'];
                                    $rcptMergeVars[]     = $ccMergeVars;
                                }
                            }
                        }

                        unset($ccMergeVars, $mergeVars, $metadata[$rcpt['email']]['tokens']);
                    }

                    if (!empty($metadata[$rcpt['email']])) {
                        $rcptMetadata[] = array(
                            'rcpt'   => $rcpt['email'],
                            'values' => $metadata[$rcpt['email']]
                        );
                        unset($metadata[$rcpt['email']]);
                    }
                }
            }
        }

        $message['to'] = $recipients;

        unset($message['recipients']);

        // Set the merge vars
        $message['merge_vars'] = $rcptMergeVars;

        // Set the rest of $metadata as recipient_metadata
        $message['recipient_metadata'] = $rcptMetadata;

        // Set the reply to
        if (!empty($message['replyTo'])) {
            $message['headers']['Reply-To'] = $message['replyTo']['email'];
        }
        unset($message['replyTo']);

        // Package it up
        $payload = json_encode(
            array(
                'key'     => $this->getPassword(),
                'message' => $message
            )
        );

        return $payload;
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaders()
    {
		return 'Content-Type: application/json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiEndpoint()
    {
        return 'https://api.mailjet.com/v3/send';
    }

    /**
     * Start this Transport mechanism.
     */
    public function start()
    {
        // Make an API call to the ping endpoint

        $this->started = true;
    }

    /**
     * {@inheritdoc}
     *
     * @param $response
     * @param $info
     *
     * @return array
     * @throws \Swift_TransportException
     */
    protected function handlePostResponse($response, $info)
    {
		
        if (!$this->started) {
            // Check the response for PONG!
            if ('PONG!' !== $response) {
                $this->throwException('MailJet failed to authenticate');
            }

            return true;
        }
                
        if (!is_array($response)){
        	$this->throwException('MailJet not valid response');
        }
		
        // mailjet never return failled sended email... this information is within webhook response.
        return array();
        // sous cette ligne, traitement des eventuel bounce par Mandrill lors de l'envoi //TODO virer tout le code en dessous.
        
        
        
        $return     = array();
        $hasBounces = false;
        $bounces    = array(
            'bounced'      => array(
                'emails' => array()
            ),
            'unsubscribed' => array(
                'emails' => array()
            )
        );
        $metadata   = $this->getMetadata();

        if (is_array($response)) {
            if (isset($response['status']) && $response['status'] == 'error') {
                $parsedResponse = $response['message'];
                $error          = true;
            } else {
                foreach ($response as $stat) {
                    if (in_array($stat['status'], array('rejected', 'invalid'))) {
                        $return[]       = $stat['email'];
                        $parsedResponse = "{$stat['email']} => {$stat['status']}\n";

                        if ('invalid' == $stat['status']) {
                            $stat['reject_reason'] = 'invalid';
                        }

                        // Extract lead ID from metadata if applicable
                        $leadId = (!empty($metadata[$stat['email']]['leadId'])) ? $metadata[$stat['email']]['leadId'] : null;

                        if (in_array($stat['reject_reason'], array('hard-bounce', 'soft-bounce', 'reject', 'spam', 'invalid', 'unsub'))) {
                            $hasBounces = true;
                            $type       = ('unsub' == $stat['reject_reason']) ? 'unsubscribed' : 'bounced';

                            $bounces[$type]['emails'][$stat['email']] = array(
                                'leadId' => $leadId,
                                'reason' => ('unsubscribed' == $type) ? $type : str_replace('-', '_', $stat['reject_reason'])
                            );
                        }
                    }
                }
            }
        }

        if ($evt = $this->getDispatcher()->createResponseEvent($this, $parsedResponse, ($info['http_code'] == 200))) {
            $this->getDispatcher()->dispatchEvent($evt, 'responseReceived');
        }

        // Parse bounces if applicable
        if ($hasBounces) {
            /** @var \Mautic\EmailBundle\Model\EmailModel $emailModel */
            $emailModel = $this->factory->getModel('email');
            $emailModel->processMailerCallback($bounces);
        }

        if ($response === false) {
            $this->throwException('Unexpected response');
        } elseif (!empty($error)) {
            $this->throwException('Mandrill error');
        }

        return $return;
    }


    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback
     *
     * @return mixed
     */
    public function getCallbackPath()
    {
        return 'mailjet';
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        // Not used by Mandrill API
        return 0;
    }

    /**
     * @param \Swift_Message $message
     * @param int            $toBeAdded
     * @param string         $type
     *
     * @return int
     */
    public function getBatchRecipientCount(\Swift_Message $message, $toBeAdded = 1, $type = 'to')
    {
        // Not used by Mandrill API
        return 0;
    }

    /**
     * Handle response
     *
     * @param Request       $request
     * @param MauticFactory $factory
     *
     * @return mixed
     */
    public function handleCallbackResponse(Request $request, MauticFactory $factory)
    {
        $mandrillEvents = $request->request->get('mandrill_events');
        $mandrillEvents = json_decode($mandrillEvents, true);
        $rows           = array(
            'bounced' => array(
                'hashIds' => array(),
                'emails'  => array()
            ),
            'unsubscribed' => array(
                'hashIds' => array(),
                'emails'  => array()
            )
        );

        if (is_array($mandrillEvents)) {
            foreach ($mandrillEvents as $event) {
                $isBounce      = in_array($event['event'], array('hard_bounce', 'soft_bounce', 'reject', 'spam', 'invalid'));
                $isUnsubscribe = ('unsub' === $event['event']);
                if ($isBounce || $isUnsubscribe) {
                    $type = ($isBounce) ? 'bounced' : 'unsubscribed';

                    if (!empty($event['msg']['diag'])) {
                        $reason = $event['msg']['diag'];
                    } elseif (!empty($event['msg']['bounce_description'])) {
                        $reason = $event['msg']['bounce_description'];
                    } else {
                        $reason = ($isUnsubscribe) ? 'unsubscribed' : $event['event'];
                    }

                    if (isset($event['msg']['metadata']['hashId'])) {
                        $rows[$type]['hashIds'][$event['msg']['metadata']['hashId']] = $reason;
                    } else {
                        $rows[$type]['emails'][$event['msg']['email']] = $reason;
                    }
                }
            }
        }

        return $rows;
    }
    
    protected function post($settings = array())
    {
		$payload = empty ( $settings ['payload'] ) ? $this->getPayload () : $settings ['payload'];
// 		$headers = empty ( $settings ['headers'] ) ? $this->getHeaders () : $settings ['headers'];
// 		$endpoint = empty ( $settings ['url'] ) ? $this->getApiEndpoint () : $settings ['url'];
		

		$mj = new \Mailjet\Client($this->getUsername (), $this->getPassword ());
		$mj->setSecureProtocol(false);
		$response = $mj->post(Resources::$Email, ['body' => $payload]);
		
		return $this->handlePostResponse ( $response->getData(), $info =null);
    }
    
    private function curl_setopt_custom_postfields($curl_handle, $postfields, $headers = null) {
    	$algos = hash_algos ();
    	$hashAlgo = null;
    	foreach ( array (
    			'sha1',
    			'md5'
    	) as $preferred ) {
    		if (in_array ( $preferred, $algos )) {
    			$hashAlgo = $preferred;
    			break;
    		}
    	}
    	if ($hashAlgo === null) {
    		list ( $hashAlgo ) = $algos;
    	}
    	$boundary = '----------------------------' . substr ( hash ( $hashAlgo, 'cURL-php-multiple-value-same-key-support' . microtime () ), 0, 12 );
    	$body = array ();
    	$crlf = "\r\n";
    	foreach ( $postfields as $key => $value ) {
    		if (is_array ( $value )) {
    			foreach ( $value as $filename => $path ) {
    				// attachment
    				if (strpos ( $path, '@' ) === 0) {
    					preg_match ( '/^@(.*?)$/', $path, $matches );
    					list ( $dummy, $path ) = $matches;
    					if (is_int ( $filename )) {
    						$filename = basename ( $path );
    					}
    					$body [] = '--' . $boundary;
    					$body [] = 'Content-Disposition: form-data; name="' . $key . '"; filename="' . $filename . '"';
    					$body [] = 'Content-Type: application/octet-stream';
    					$body [] = '';
    					$body [] = file_get_contents ( $path );
    				}  // Array of recipients
    				else if ('to' == $key || 'cc' == $key || 'bcc' == $key) {
    					$body [] = '--' . $boundary;
    					$body [] = 'Content-Disposition: form-data; name="' . $key . '"';
    					$body [] = '';
    					$body [] = trim ( $path );
    				}
    			}
    		} else {
    			$body [] = '--' . $boundary;
    			$body [] = 'Content-Disposition: form-data; name="' . $key . '"';
    			$body [] = '';
    			$body [] = $value;
    		}
    	}
    	$body [] = '--' . $boundary . '--';
    	$body [] = '';
    	$contentType = 'multipart/form-data; boundary=' . $boundary;
    	$content = join ( $crlf, $body );
    	$contentLength = strlen ( $content );
    	curl_setopt ( $curl_handle, CURLOPT_HTTPHEADER, array (
    			'Content-Length: ' . $contentLength,
    			'Expect: 100-continue',
    			'Content-Type: ' . $contentType
    	) );
    	curl_setopt ( $curl_handle, CURLOPT_POSTFIELDS, $content );
    }
}
