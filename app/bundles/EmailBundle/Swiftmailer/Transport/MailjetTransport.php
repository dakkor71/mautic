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
class MailjetTransport extends AbstractTokenSmtpTransport implements InterfaceCallbackTransport
{
	
	/**
	 * {@inheritdoc}
	 */
	public function __construct($host = 'localhost', $port = 25, $security = null)
	{
		parent::__construct('in-v3.mailjet.com', 587, 'tls');
	
		$this->setAuthMode('login');
	}
	
	/**
	 * Do whatever is necessary to $this->message in order to deliver a batched payload. i.e. add custom headers, etc
	 *
	 * @return void
	 */
	abstract protected function prepareMessage(){

		//add leadIdHash to track this email
		$header = $this->message->getHeaders()->addTextHeader('X-MJ-CUSTOMID',$this->message->leadIdHash);

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
		// $this->factory->getLogger()->log('error',serialize($postData));
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
    
    
}