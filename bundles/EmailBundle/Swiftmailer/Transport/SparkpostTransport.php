<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @see         https://github.com/SlowProg/SparkPostSwiftMailer/blob/master/SwiftMailer/SparkPostTransport.php for additional source reference
 */

namespace Mautic\EmailBundle\Swiftmailer\Transport;

use Mautic\CoreBundle\Factory\MauticFactory;
use SparkPost\APIResponseException;

use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Ivory\HttpAdapter\Guzzle6HttpAdapter;

/**
 * Class SparkpostTransport
 * The referrence class for this was provided by
 *
 */
class SparkpostTransport extends AbstractTokenArrayTransport implements \Swift_Transport, InterfaceTokenTransport
{
    /**
     * @var string|null
     */
    protected $apiKey;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * SparkpostTransport constructor.
     *
     * @param $apiKey
     */
    public function __construct($apiKey)
    {
        $this->setApiKey($apiKey);
        $this->getDispatcher();
    }

    /**
     * @param string $apiKey
     *
     * @return $this
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return null|string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return SparkPost
     * @throws \Swift_TransportException
     */
    protected function createSparkPost()
    {
        if ($this->apiKey === null) {
            throw new \Swift_TransportException('Cannot create instance of \SparkPost\SparkPost while API key is NULL');
        }
        $httpAdapter = new Guzzle6HttpAdapter(new Client());
        $sparky      = new SparkPost($httpAdapter, ['key' => $this->apiKey]);

        return $sparky;
    }

    /**
     * @param \Swift_Mime_Message $message
     * @param null               $failedRecipients
     *
     * @return int Number of messages sent
     */
    public function send(\Swift_Mime_Message $message, &$failedRecipients = null)
    {
        if ($event = $this->getDispatcher()->createSendEvent($this, $message)) {
            $this->getDispatcher()->dispatchEvent($event, 'beforeSendPerformed');
            if ($event->bubbleCancelled()) {

                return 0;
            }
        }

        try {
            $sparkPost        = $this->createSparkPost();
            $sparkPostMessage = $this->getSparkPostMessage($message);
            $response         = $sparkPost->transmission->send($sparkPostMessage);
            $sendCount        = $response['results']['total_accepted_recipients'];
        } catch (APIResponseException $e) {
            $this->throwException($e->getAPIMessage());
        }

        if ($event) {
            if ($sendCount > 0) {
                $event->setResult(\Swift_Events_SendEvent::RESULT_SUCCESS);
            } else {
                $event->setResult(\Swift_Events_SendEvent::RESULT_FAILED);
            }
            $this->getDispatcher()->dispatchEvent($event, 'sendPerformed');
        }

        return $sendCount;
    }

    /**
     * https://jsapi.apiary.io/apis/sparkpostapi/introduction/subaccounts-coming-to-an-api-near-you-in-april!.html
     *
     * @param \Swift_Mime_Message $message
     *
     * @return array SparkPost Send Message
     * @throws \Swift_SwiftException
     */
    public function getSparkPostMessage(\Swift_Mime_Message $message)
    {
        $tags      = [];
        $inlineCss = null;

        $this->message = $message;
        $metadata      = $this->getMetadata();
        $mauticTokens  = $mergeVars = $mergeVarPlaceholders = [];

        // Sparkpost uses {{ name }} for tokens so Mautic's need to be converted; although using their {{{ }}} syntax to prevent HTML escaping
        if (!empty($metadata)) {
            $metadataSet  = reset($metadata);
            $tokens       = (!empty($metadataSet['tokens'])) ? $metadataSet['tokens'] : [];
            $mauticTokens = array_keys($tokens);

            $mergeVars = $mergeVarPlaceholders = [];
            foreach ($mauticTokens as $token) {
                $mergeVars[$token]            = strtoupper(preg_replace("/[^a-z0-9]+/i", "", $token));
                $mergeVarPlaceholders[$token] = '{{{ '.$mergeVars[$token].' }}}';
            }
        }

        $message = $this->messageToArray($mauticTokens, $mergeVarPlaceholders, true);

        if (isset($message['headers']['X-MC-InlineCSS'])) {
            $inlineCss = $message['headers']['X-MC-InlineCSS'];
        }
        if (isset($message['headers']['X-MC-Tags'])) {
            $tags = explode(',', $message['headers']['X-MC-Tags']);
        }

        $recipients = [];
        foreach ($message['recipients']['to'] as $to) {
            $recipient = [
                'address'           => $to,
                'substitution_data' => []
            ];

            if (isset($metadata[$to['email']])) {
                foreach ($metadata[$to['email']]['tokens'] as $token => $value) {
                    $recipient['substitution_data'][$mergeVars[$token]] = $value;
                }
            }

            $recipients[] = $recipient;
        }

        if (isset($message['replyTo'])) {
            $headers['Reply-To'] = (!empty($message['replyTo']['name']))
                ?
                sprintf('%s <%s>', $message['replyTo']['email'], $message['replyTo']['name'])
                :
                $message['replyTo']['email'];
        }

        $sparkPostMessage = [
            'html'           => $message['html'],
            'text'           => $message['text'],
            'from'           => (!empty($message['from']['name'])) ? $message['from']['name'].' <'.$message['from']['email'].'>'
                : $message['from']['email'],
            'subject'        => $message['subject'],
            'recipients'     => $recipients,
            'cc'             => array_values($message['recipients']['cc']),
            'bcc'            => array_values($message['recipients']['bcc']),
            'headers'        => $message['headers'],
            'inline_css'     => $inlineCss,
            'tags'           => $tags
        ];

        if (!empty($message['attachments'])) {

            $sparkPostMessage['attachments'] = $message['attachments'];
        }

        return $sparkPostMessage;
    }

    /**
     * @return int
     */
    public function getMaxBatchLimit()
    {
        return 5000;
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
        return (count($message->getTo()) + count($message->getCc()) + count($message->getBcc()) + $toBeAdded);
    }
}
