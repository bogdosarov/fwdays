<?php
/**
 * Created by JetBrains PhpStorm.
 * User: zion
 * Date: 15.03.13
 * Time: 15:02
 * To change this template use File | Settings | File Templates.
 */

namespace Stfalcon\Bundle\EventBundle\Command;


use Stfalcon\Bundle\EventBundle\Repository\MailQueueRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Stfalcon\Bundle\EventBundle\Entity\Mail;

class StfalconMailerCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('stfalcon:mailer')
            ->setDescription('Send message from queue')
            ->addOption('amount', null, InputOption::VALUE_NONE, 'Amount of mails which will send per operation. Default 10.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $limit=10;

        if ($input->getOption('amount')) {
            $limit=(int)$input->getOption('amount');
        }


        $em =$this->getContainer()->get('doctrine')->getEntityManager('default');
        $mailer = $this->getContainer()->get('mailer');

        /** @var $queueRepository MailQueueRepository */
        $queueRepository=$em->getRepository('StfalconEventBundle:MailQueue');
        $mailsQueue = $queueRepository->findBy(array('isSent' => 0),array(),$limit);

        /** @var $mail Mail */
        foreach($mailsQueue as $item){
            $user=$item->getUser();
            $mail=$item->getMail();

            if (!($user && $mail)){
                $em->remove($item);
                $em->flush();
                continue;
            }

            $text = $mail->replace(
                array(
                    '%fullname%' => $user->getFullname(),
                    '%user_id%' => $user->getId(),
                )
            );

            $message = \Swift_Message::newInstance()
                ->setSubject($mail->getTitle())
                // @todo refact
                ->setFrom('orgs@fwdays.com', 'Frameworks Days')
                ->setTo($user->getEmail())
                ->setBody($text, 'text/html');

            if ($mailer->send($message)){

                $mail->setSentMessages($mail->getSentMessages()+1);
                $item->setIsSent(true);

                $em->persist($mail);
                $em->persist($item);
                $em->flush();
            }

        }
}
}