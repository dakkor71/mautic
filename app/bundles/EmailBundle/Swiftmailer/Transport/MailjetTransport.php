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
    	$payload=array();

        $message = $this->messageToArray();

        $payload['FromEmail']=$message['from']['email'];
        $payload['FromName']=$message['from']['name'];
        
        $payload['Recipients']=array();
        $payload['To']=array();
        $payload['Cc']=array();
        $payload['Bcc']=array();

        foreach ($message['recipients']['to'] as $aT => $dataTo){
        	$payload['Recipients'][]=array('Email'=>$dataTo['email'],'Name'=>$dataTo['name']);
        	$payload['To'][]=$dataTo['email'];
        }
        foreach ($message['recipients']['cc'] as $aT => $dataCc){
        	$payload['Recipients'][]=array('Email'=>$dataCc['email'],'Name'=>$dataCc['name']);
        	$payload['Cc'][]=$dataCc['email'];
        }
        foreach ($message['recipients']['bcc'] as $aT => $dataBcc){
        	$payload['Recipients'][]=array('Email'=>$dataBcc['email'],'Name'=>$dataBcc['name']);
        	$payload['Bcc'][]=$dataBcc['email'];
        }        
        
        
//         $metadata = $this->getMetadata();

        if (isset($this->message->leadIdHash)) {
        	$payload['Mj-CustomID']=$this->message->leadIdHash;
        }
        
//         var_dump(array($this->message->leadIdHash=>$payload['To'][0]));
        
        // Set the reply to
        if (!empty($message['replyTo'])) {
        	if (!isset( $payload['headers'])){ $payload['headers']=array();}
            $payload['Headers']['Reply-To'][] = $message['replyTo']['email'];
        }
        
        if (!empty($message['file_attachments'])) {
        	//TODO not used ATM
//         	if (!isset( $payload['headers'])){ $payload['headers']=array();}
//         	$payload['Headers']['Reply-To'][] = $message['replyTo']['email'];
        }       

        if (!empty($message['replyTo'])) {
        	if (!isset( $payload['headers'])){ $payload['headers']=array();}
        	$payload['Headers']['Reply-To'][] = $message['replyTo']['email'];
        }        
        
        $payload['Subject']=$message['subject'];
        
        $payload['Text-part']=$message['text'];
        $payload['Html-part']=$message['html'];
// 		var_dump($payload);
// 		die('');
        return ($payload);
    }

    /**
     * {@inheritdoc}
     */
    protected function getHeaders()
    {
    
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getApiEndpoint()
    {
    	
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
		/*
        if (!$this->started) {
            // Check the response for PONG!
            if ('PONG!' !== $response) {
                $this->throwException('MailJet failed to authenticate');
            }

            return true;
        }
        */
                
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
        // Not used by Mailjet API
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
        // Not used by Mailjet API
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
		$postData = json_decode($request->getContent(), true);
		$this->factory->getLogger()->log('error',serialize($postData));
	   	$rows = array (
				'bounced' => array (
						'hashIds' => array (),
						'emails' => array () 
				),
				'unsubscribed' => array (
						'hashIds' => array (),
						'emails' => array () 
				) 
		);
		
		if (is_array ( $postData ) && isset ( $postData ['event'] )) {
			// Mailjet API callback V1
			$events = array (
					$postData 
			);
		} elseif (is_array ( $postData )) {
			// Mailjet API callback V2
			$events = $postData;
		} else {
			// respone must be an array
			return $rows;
		}
		
		foreach ( $events as $event ) {
			if (in_array ( $event ['event'], array (
					'bounce',
					'blocked',
					'spam',
					'unsub' 
			) )) {
				if ($event ['event'] === 'bounce' || $event ['event'] === 'blocked') {
					$reason = $event ['error_related_to'] . ' : '. $event ['error'];
					$type = 'bounced';
				} elseif ($event ['event'] === 'spam') {
					$reason = 'User reported email as spam, source :' . $event ['source'];
					$type = 'bounced';
				} elseif ($event ['event'] === 'unsub') {
					$reason = 'User unsubscribed';
					$type = 'unsubscribed';
				}
				
				if (isset ( $event ['CustomID'] )) {
					$rows [$type] ['hashIds'] [$event ['CustomID']] = $reason;
				} else {
					$rows [$type] ['emails'] [$event ['email']] = $reason;
				}
			}
		}
		return $rows;
    }
    
    protected function post($settings = array())
    {
		$payload = empty ( $settings ['payload'] ) ? $this->getPayload () : $settings ['payload'];

		$mj = new \Mailjet\Client($this->getUsername (), $this->getPassword ());
		$mj->setSecureProtocol(false);
		$response = $mj->post(Resources::$Email, ['body' => $payload]);

		return $this->handlePostResponse ( $response->getData(), $info =null);
    }
    
}