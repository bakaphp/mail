<?php

namespace Baka\Mail;

use Phalcon\Queue\Beanstalk\Extended as BeanstalkExtended;
use Phalcon\Queue\Beanstalk\Job;
use \Exception as Exception;

trait JobTrait
{

    /**
     * Run the email queue from phalcon CLI
     * php cli/app.php Email generalQueue
     *
     * @return void
     */
    public function generalQueueAction($queueName = null)
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

                $emailInfo = $job->getBody();

                //default template
                $template = 'templates/email';

                //specify template
                if (array_key_exists('template', $emailInfo)) {
                    $template = $emailInfo['template'];
                }

                //subject and from
                $subject = $emailInfo['subject'];
                $from = array($this->config->email->from->email => $this->config->email->from->name);

                //to who?
                //if in producto realuser email, development? max@mctekk.com email
                $to = $config->application->production ? $emailInfo['to'] : [$this->config->email->debug->from->email => $this->config->email->debug->from->name];

                //email template and replace variables
                $emailHtml = $di->getViewSimple()->render($template, $emailInfo);

                //email configuration
                $transport = Swift_SmtpTransport::newInstance($config->email->host, $config->email->port);
                $transport->setUsername($config->email->username);
                $transport->setPassword($config->email->password);
                $swift = Swift_Mailer::newInstance($transport);

                $message = new Swift_Message($subject);
                $message->setFrom($from);
                $message->setBody($emailHtml, 'text/html');
                $message->setTo($to);
                //$message->addPart($text, 'text/plain');

                $failures = [];
                if ($recipients = $swift->send($message, $failures)) {
                    $sendTo = each($to);
                    $this->log->addInfo("EmailTask Message successfully sent to: {$sendTo['key']}");
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
