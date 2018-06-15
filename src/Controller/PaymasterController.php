<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Utils\Mailer;


/** @Route("/paymaster") */
class PaymasterController extends Controller
{
    /**
     * @Route("/payment-notification/", name="paymaster_payment_notification")
     */
    public function paymentNotificationAction(Request $request)
    {
        $id = $request->get('invoice_id');
        $em = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */
        /** @var $invoice \App\Entity\Invoice */
        $invoice = $em->getRepository('App:Invoice')->createQueryBuilder('i')
            ->leftJoin('i.status','s')->addSelect('s')
            ->andWhere('s.title = :title')->setParameter('title', 'Не оплачен')
            ->andWhere('i.id = :id')->setParameter('id', $id)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult()
        ;

        $status = $em->getRepository('App:Status')->findOneBy(['title' => 'Оплачен']);

        if ($invoice and $status) {
            $invoice->setStatus($status);

            $em->persist($invoice);
            $em->flush();

            $subject = 'Статус счёта '.$invoice->getNumber(). ' изменён на "Оплачен".';
            $body    = $subject.' http://invoice.drcooper.ru/invoices/view-'.$invoice->getId();

            Mailer::send($invoice->getUser()->getEmail(), $subject, $body);

            return new Response('YES', 200, ['Content-type' => 'text/plain']);
        } else {
            return new Response('NO', 200, ['Content-type' => 'text/plain']);
        }
    }

    /**
     * @Route("/success/", name="paymaster_success")
     */
    public function successAction(Request $request)
    {
        return $this->redirect('http://drcooper.ru/success/?'.http_build_query($request->query->all()));
    }

    /**
     * @Route("/failure/", name="paymaster_failure")
     */
    public function failureAction(Request $request)
    {
        $id = $request->request->get('invoice_id');

        $em = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */
        /** @var $invoice \App\Entity\Invoice */
        $invoice = $em->getRepository('App:Invoice')->createQueryBuilder('i')
            ->leftJoin('i.status','s')->addSelect('s')
            ->andWhere('s.title = :title')->setParameter('title', 'Не оплачен')
            ->andWhere('i.id = :id')->setParameter('id', $id)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult()
        ;

        $status = $em->getRepository('App:Status')->findOneBy(['title' => 'Неудачный']);

        if ($invoice and $status) {
            $invoice->setStatus($status);

            $em->persist($invoice);
            $em->flush();

            $subject = 'Статус счёта '.$invoice->getNumber(). ' изменён на "Неудачный".';
            $body    = $subject.' http://invoice.drcooper.ru/invoices/view-'.$invoice->getId();

            Mailer::send($invoice->getUser()->getEmail(), $subject, $body);

            return $this->redirect('http://drcooper.ru/failure/?'.http_build_query($request->request->all()));
        }

        return new Response('YES', 200, ['Content-type' => 'text/plain']);

    }

    /**
     * @Route("/invoice-confirmation/", name="paymaster_invoice_confirmation")
     */
    public function invoiceConfirmationAction(Request $request)
    {
        $id = $request->get('invoice_id');
        $em = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */
        /** @var $invoice \App\Entity\Invoice */
        $invoice = $em->getRepository('App:Invoice')->createQueryBuilder('i')
            ->leftJoin('i.status','s')->addSelect('s')
            ->andWhere('s.title = :title')->setParameter('title', 'Не оплачен')
            ->andWhere('i.id = :id')->setParameter('id', $id)
            ->setMaxResults(1)->getQuery()->getOneOrNullResult()
        ;

        if ($invoice) {
            return new Response('YES', 200, ['Content-type' => 'text/plain']);
        } else {
            return new Response('NO', 200, ['Content-type' => 'text/plain']);
        }
    }
}