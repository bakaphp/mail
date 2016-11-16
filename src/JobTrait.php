<?php

namespace Baka\Mail;

use Phalcon\Queue\Beanstalk\Extended as BeanstalkExtended;
use Phalcon\Queue\Beanstalk\Job;
use \Exception as Exception;
use \Swift_Mime_Message;
use \Swift_SmtpTransport;

trait JobTrait
{

    /**
     * @description("Email queue")
     *
     * @param({'type'='string', 'name'='queueName', 'description'='name of the queue , default email_queue' })
     *
     * @return void
     */
    public function mailQueueAction($queueName)
    {
        if (empty($queueName)) {
            echo "\nYou have to define a queue name.\n\n";
            return;
        }

        if (!is_object($this->config->beanstalk)) {
            echo "\nNeed to configure beanstalkd on your phalcon configuration.\n\n";
            return;
        }

        if (!is_object($this->config->email)) {
            echo "\nNeed to configure email on your phalcon configuration.\n\n";
            return;
        }

        //call queue
        $queue = new BeanstalkExtended(array(
            'host' => $this->config->beanstalk->host,
            'prefix' => $this->config->beanstalk->prefix,
        ));

        //dependent variables
        $config = $this->config;
        $di = \Phalcon\DI\FactoryDefault::getDefault();

        //call queue tube
        $queue->addWorker($queueName[0], function (Job $job) use ($di, $config) {

            try {

                $message = $job->getBody();

                if (!$message instanceof Swift_Mime_Message) {
                    $this->log->addError('Something went wrong with the message we are trying to send ', $message);
                    return;
                }

                //email configuration
                $transport = \Swift_SmtpTransport::newInstance($config->email->host, $config->email->port);
                $transport->setUsername($config->email->username);
                $transport->setPassword($config->email->password);
                $swift = \Swift_Mailer::newInstance($transport);

                $failures = [];
                if ($recipients = $swift->send($message, $failures)) {

                    $this->log->addInfo("EmailTask Message successfully sent to:", $message->getTo());

                } else {
                    $this->log->addError("EmailTask There was an error: ", $failures);
                }
            } catch (Exception $e) {
                $this->log->addError($e->getMessage());
                echo $e->getMessage() . "\n";
            }

            // It's very important to send the right exit code!
            exit(0);
        });

        // Start processing queues
        $queue->doWork();
    }
}
