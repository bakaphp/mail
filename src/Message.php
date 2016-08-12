<?php

namespace Baka\Mail;

/**
 * Class Message
 *
 * @package Phalcon\Mailer
 */
class Message extends \Phalcon\Mailer\Message
{
    protected $queueName = 'email_queue';
    protected $viewPath = null;
    protected $params = null;
    protected $viewsDirLocal = null;

    /**
     * Set the body of this message, either as a string, or as an instance of
     * {@link \Swift_OutputByteStream}.
     *
     * @param mixed $content
     * @param string $contentType optional
     * @param string $charset     optional
     *
     * @return $this
     *
     * @see \Swift_Message::setBody()
     */
    public function content($content, $contentType = self::CONTENT_TYPE_HTML, $charset = null)
    {
        //if we have params thats means we are using a template
        if (is_array($this->params)) {
            $content = $this->getManager()->setRenderView($this->viewPath, $this->params, $this->viewsDirLocal);
        }

        $this->getMessage()->setBody($content, $contentType, $charset);

        return $this;
    }

    /**
     * Send the given Message like it would be sent in a mail client.
     *
     * All recipients (with the exception of Bcc) will be able to see the other
     * recipients this message was sent to.
     *
     * Recipient/sender data will be retrieved from the Message object.
     *
     * The return value is the number of recipients who were accepted for
     * delivery.
     *
     * Events:
     * - mailer:beforeSend
     * - mailer:afterSend
     *
     * @return int
     *
     * @see \Swift_Mailer::send()
     */
    public function send()
    {
        $eventManager = $this->getManager()->getEventsManager();
        if ($eventManager) {
            $result = $eventManager->fire('mailer:beforeSend', $this);
        } else {
            $result = true;
        }

        if ($result === false) {
            return false;
        }

        $this->failedRecipients = [];

        //send to queue
        $queue = $this->getManager()->getQueue();
        //$queueName = $this->

        $queue->putInTube($this->queueName, $this->getMessage());

        /* $count = $this->getManager()->getSwift()->send($this->getMessage(), $this->failedRecipients);

    if ($eventManager) {
    $eventManager->fire('mailer:afterSend', $this, [$count, $this->failedRecipients]);
    }
    return $count;*/
    }

    /**
     * Set the queue name if the user wants to shange it
     *
     * @param string $queuName
     *
     * @return $this
     */
    public function queue($queue)
    {
        $this->queueName = $queue;
        return $this;
    }

    /**
     * Set variables to views
     *
     * @param string $params
     *
     * @return $this
     */
    public function params($params)
    {
        $this->params = $params;
        return $this;
    }

    /**
     * The local path to the folder viewsDir only this message. (OPTIONAL)
     *
     * @param string $dir
     *
     * @return $this
     */
    public function viewDir($dir)
    {
        $this->viewsDirLocal = $dir;
        return $this;
    }

    /**
     * view relative to the folder viewsDir (REQUIRED)
     *
     * @param string $template
     *
     * @return $this
     */
    public function template($template)
    {
        $this->viewPath = $template;
        return $this;
    }

}
