<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\EmailBundle\MonitoredEmail\Message;
use Mautic\EmailBundle\Swiftmailer\Amazon\AmazonCallback;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AmazonTransport.
 */
class AmazonTransport extends \Swift_SmtpTransport implements CallbackTransportInterface, BounceProcessorInterface, UnsubscriptionProcessorInterface
{
    /**
     * @var AmazonCallback
     */
    private $amazonCallback;

    /**
     * AmazonTransport constructor.
     *
     * @param string $host
     */
    public function __construct($host, AmazonCallback $amazonCallback)
    {
        parent::__construct($host, 2587, 'tls');
        $this->setAuthMode('login');
        $this->amazonCallback = $amazonCallback;
    }

    /**
     * Returns a "transport" string to match the URL path /mailer/{transport}/callback.
     *
     * @return string
     */
    public function getCallbackPath()
    {
        return 'amazon';
    }

    /**
     * Handle bounces & complaints from Amazon.
     */
    public function processCallbackRequest(Request $request)
    {
        $this->amazonCallback->processCallbackRequest($request);
    }

    public function processBounce(Message $message)
    {
        $this->amazonCallback->processBounce($message);
    }

    public function processUnsubscription(Message $message)
    {
        $this->amazonCallback->processUnsubscription($message);
    }
}
