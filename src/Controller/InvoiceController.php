<?php
namespace App\Controller;

use App\Entity\Invoice;
use App\Utils\Paymaster;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/** @Route("/invoices") */
class InvoiceController extends Controller
{
    /**
     * @Route("/")
     * @Route("/list/", name="invoice_list")
     */
    public function listAction(Request $request)
    {
        $fb = $this->createFormBuilder();

        $fb->add('date_from_created_at', DateType::class, [
            'label'    => 'Дата создания от: ',
            'required' => false,
            'html5'    => true,
            'widget'   => 'single_text',
            'attr'     => ['class' => 'form-group form-control'],
        ]);
        $fb->add('date_to_created_at', DateType::class, [
            'label'    => 'Дата создания до: ',
            'input'    => 'datetime',
            'required' => false,
            'html5'    => true,
            'widget'   => 'single_text',
            'attr'     => ['class' => 'form-group form-control'],
        ]);

        $fb->add('date_from_paid_at', DateType::class, [
            'label'    => 'Дата оплаты от: ',
            'required' => false,
            'html5'    => true,
            'widget'   => 'single_text',
            'attr'     => ['class' => 'form-group form-control'],
        ]);
        $fb->add('date_to_paid_at', DateType::class, [
            'label'    => 'Дата оплаты до: ',
            'input'    => 'datetime',
            'required' => false,
            'html5'    => true,
            'widget'   => 'single_text',
            'attr'     => ['class' => 'form-group form-control'],
        ]);

        $fb->add('status', EntityType::class, [
            'label'       => 'Статус',
            'class'       => 'App:Status',
            'attr'        => ['class'  => 'form-group form-control'],
            'required'    => false,
            'placeholder' => '-- Выберите статус --',
        ]);

        $fb->add('user', EntityType::class, [
            'label'       => 'Менеджер',
            'class'       => 'App:User',
            'attr'        => ['class'  => 'form-group form-control'],
            'required'    => false,
            'placeholder' => '-- Выберите менеджера --',
        ]);

        $fb->add('find', SubmitType::class, [
            'label' => 'Найти',
            'attr'  => ['class' => 'btn btn-success'],
        ]);

        $user = $this->getUser(); /** @var $user \App\Entity\User */
        $em   = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */
        $qb   = $em->getRepository('App:Invoice')->createQueryBuilder('i')
                ->leftJoin('i.status','s')->addSelect('s')
                ->orderBy('i.id', 'DESC')
        ;
        if($user->hasRole('ROLE_MANAGER')) {
            $qb->andWhere('i.user = :user')->setParameter('user', $user);
            $qb->andWhere('s.title != :title')->setParameter('title', 'Удалённый');
        }

        if($user->hasRole('ROLE_SUPERVISOR')) {
            $qb->andWhere('s.title != :title')->setParameter('title', 'Удалённый');
        }

        $fb->setMethod('get');
        $filter_form = $fb->getForm();
        $filter_form->handleRequest($request);
        if ($request->isMethod('get') and $filter_form->isSubmitted()) {
            $date_from_created_at = $filter_form->get('date_from_created_at')->getData();
            $date_to_created_at   = $filter_form->get('date_to_created_at')->getData();
            $date_from_paid_at    = $filter_form->get('date_from_paid_at')->getData();
            $date_to_paid_at      = $filter_form->get('date_to_paid_at')->getData();
            $status               = $filter_form->get('status')->getData();

            if ($date_from_created_at) {
                $qb->andWhere('i.created_at >= :date_from')->setParameter('date_from', $date_from_created_at);
            }

            if ($date_to_created_at) {
                $qb->andWhere('i.created_at <= :date_to')->setParameter('date_to', $date_to_created_at);
            }

            if ($date_from_paid_at) {
                $qb->andWhere('i.paid_at >= :date_from')->setParameter('date_from', $date_from_paid_at);
                $qb->andWhere('s.title = :title')->setParameter('title', 'Оплачен');

            }

            if ($date_to_paid_at) {
                $qb->andWhere('i.paid_at <= :date_to')->setParameter('date_to', $date_to_paid_at);
                $qb->andWhere('s.title = :title')->setParameter('title', 'Оплачен');
            }

            if ($status) {
                $qb->andWhere('i.status = :status')->setParameter('status', $status);
            }

            if ($filter_form->get('user')->getData()) {
                $qb->andWhere('i.user = :user')->setParameter('user', $user);
            }
        }

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setMaxPerPage(20);
        $pagerfanta->setCurrentPage($request->get('page', 1));

        $invoice_choices = [];
        foreach($pagerfanta->getCurrentPageResults() as $invoice) { /** @var $invoice \App\Entity\Invoice */
            $invoice_choices[$invoice->getId()] = $invoice->getId();
        }

        $fb2 = $this->createFormBuilder([], []);

        $fb2->add('invoices', ChoiceType::class, [
            'multiple' => true,
            'expanded' => true,
            'choices'  => $invoice_choices,
            'attr'     => ['class' => 'invoice_checkbox'],
        ]);

        $fb2->add('user', EntityType::class, [
            'label'       => 'Менеджер',
            'class'       => 'App:User',
            'constraints' => new Assert\NotBlank(),
            'attr'        => ['class'  => 'form-group form-control'],
        ]);

        $fb2->add('change_user', SubmitType::class, [
            'label' => 'Поменять менеджера',
            'attr'  => ['class' => 'btn btn-success mt-4'],
        ]);

        $form = $fb2->getForm();
        $form->handleRequest($request);

        if($user->hasRole('ROLE_ADMIN') or $user->hasRole('ROLE_SUPERVISOR')) {
            if ($request->isMethod('post') and $form->isSubmitted()) {
                $invoice_ids = $form->get('invoices')->getData();
                if (empty($invoice_ids)) {
                    $this->addFlash('error', 'Не выбраны счета');
                } else {
                    $invoices    = $em->getRepository('App:Invoice')->createQueryBuilder('i')
                        ->andWhere('i.id IN (:ids)')->setParameter('ids', $invoice_ids)
                        ->getQuery()->getResult();

                    foreach ($invoices as $invoice) { /** @var $invoice \App\Entity\Invoice */
                        $invoice->setUser($form->get('user')->getData());
                        $em->persist($invoice);
                        $em->flush();
                    }

                    $this->addFlash('success', 'Поменяли менеджера успешно');
                }
            }
        }

        return $this->render('invoice/list.html.twig', [
            'pagerfanta'  => $pagerfanta,
            'filter_form' => $filter_form->createView(),
            'form'        => $form->createView(),
        ]);
    }

    /**
     * @Route("/copy-{id}/", name="invoice_copy")
     * @Route("/create/", name="invoice_create")
     */
    public function createAction(Request $request)
    {
        $id   = $request->get('id');
        $em   = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */
        $user = $this->getUser(); /** @var $user \App\Entity\User */
        /** @var $invoice \App\Entity\Invoice */

        $invoice = new Invoice();

        if ($id) {
            /** @var $parent_invoice \App\Entity\Invoice */
            $parent_invoice = $em->getRepository('App:Invoice')->createQueryBuilder('i')
                ->leftJoin('i.status','s')->addSelect('s')
                ->andWhere('i.id = :id')->setParameter('id' ,$id)
                ->setMaxResults(1)->getQuery()->getOneOrNullResult()
            ;
            if (!$parent_invoice) {
                throw $this->createNotFoundException('Not found');
            }

            $invoice->setTitle($parent_invoice->getTitle());
            $invoice->setPhone($parent_invoice->getPhone());
            $invoice->setEmail($parent_invoice->getEmail());
            $invoice->setNumber($parent_invoice->getNumber());
            $invoice->setSum($parent_invoice->getSum());
            $invoice->setCommentary($parent_invoice->getCommentary());
        }

        $fb = $this->createFormBuilder($invoice);

        $fb->add('number', TextType::class, [
            'label'       => 'Номер заказа',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('title', TextType::class, [
            'label'       => 'Наименование',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('phone', TextType::class, [
            'label'       => 'Телефон плательщика',
            'required'    => false,
            'constraints' => [
                new Assert\Regex([
                    'pattern' => '/^\+[7]\d{10}$/u',
                    'message' => 'Введите номер телефона плательщика в формате +79009009090',
                ]),
            ],
            'attr'        => [
                'class'       => 'form-group form-control',
                'pattern'     => '\+[7]\d{10}',
                'title'       => 'Введите номер телефона плательщика в формате +79009009090',
            ],
        ]);

        $fb->add('email', EmailType::class, [
            'label'       => 'Почта плательщика',
            'required'    => false,
            'attr'        => [
                'class'       => 'form-group form-control',
                'title'       => 'Введите электронную почту плательщика в формате user@mail.ru',
            ],
        ]);

        $fb->add('sum', MoneyType::class, [
            'label'       => 'Сумма',
            'scale'       => 2,
            'currency'    => false,
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => [
                'class'   => 'form-group form-control',
                'pattern' => '[0-9]+([\.|,][0-9]{0,2}){0,1}',
                'title'   => 'Введите сумму в формате 100.00',
            ],
        ]);

        if($user->hasRole('ROLE_ADMIN') or $user->hasRole('ROLE_SUPERVISOR')) {
            $fb->add('user', EntityType::class, [
                'label'       => 'Менеджер',
                'class'       => 'App:User',
                'constraints' => new Assert\NotBlank(),
                'attr'        => ['class'  => 'form-group form-control'],
                'data'        => $user,
            ]);
        }

        $fb->add('commentary', TextareaType::class, [
            'label'       => 'Комментарий',
            'required'    => false,
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('add', SubmitType::class, [
            'label' => 'Добавить',
            'attr'  => ['class' => 'btn btn-success mt-4'],
        ]);

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($request->isMethod('post') and $form->isSubmitted()) {
            $number     = $form->get('number')->getData();
            $title      = $form->get('title')->getData();
            $phone      = $form->get('phone')->getData();
            $email      = $form->get('email')->getData();
            $sum        = $form->get('sum')->getData();
            $commentary = $form->get('commentary')->getData();
            $user       = $user->hasRole('ROLE_MANAGER')? $user : $form->get('user')->getData();

            if ($form->isValid()) { /** @var $invoice \App\Entity\Invoice */
                $invoice->setNumber($number);
                $invoice->setTitle($title);
                $invoice->setPhone($phone);
                $invoice->setEmail($email);
                $invoice->setSum($sum);
                $invoice->setCommentary($commentary);
                $invoice->setUser($user);

                $status  = $em->getRepository('App:Status')->findOneBy(['title' => 'Не оплачен']);

                if (!$status) {
                    throw $this->createNotFoundException('Not found');
                }

                $invoice->setStatus($status);

                $em->persist($invoice);
                $em->flush();

                $invoice->setUri(Paymaster::builtinPaymentInitonlyAction($invoice));

                $em->persist($invoice);
                $em->flush();

                $this->addFlash('success', 'Счёт успешно добавлен');
                return $this->redirectToRoute('invoice_list');
            }
        }

        return $this->render('invoice/item.html.twig', [
            'form'   => $form->createView(),
            'action' => 'add',
        ]);
    }

    /**
     * @Route("/edit-{id}/", name="invoice_edit")
     */
    public function editAction(Request $request)
    {
        $id = $request->get('id');
        $em = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */
        /** @var $invoice \App\Entity\Invoice */
        $qb = $em->getRepository('App:Invoice')->createQueryBuilder('i')
            ->leftJoin('i.status','s')->addSelect('s')

            ->andWhere('i.id = :id')->setParameter('id' ,$id)
        ;

        $user = $this->getUser(); /** @var $user \App\Entity\User */
        if($user->hasRole('ROLE_MANAGER')) {
            $qb->andWhere('i.user = :user')->setParameter('user', $user);
            $qb->andWhere('s.title = :title')->setParameter('title', 'Не оплачен');
        }

        if($user->hasRole('ROLE_SUPERVISOR')) {
            $qb->andWhere('s.title = :title')->setParameter('title', 'Не оплачен');
        }

        $invoice = $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();

        if (!$invoice) {
            throw $this->createNotFoundException('Not found');
        }

        $fb = $this->createFormBuilder($invoice);

        $fb->add('number', TextType::class, [
            'label'       => 'Номер заказа',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('title', TextType::class, [
            'label'       => 'Наименование',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('phone', TextType::class, [
            'label'       => 'Телефон плательщика',
            'required'    => false,
            'attr'        => [
                'class'       => 'form-group form-control',
                'pattern'     => '\+[7]\d{10}',
                'title'       => 'Введите номер телефона плательщика в формате +79009009090',
            ],
        ]);

        $fb->add('email', EmailType::class, [
            'label'       => 'Почта плательщика',
            'required'    => false,
            'attr'        => [
                'class'       => 'form-group form-control',
                'title'       => 'Введите электронную почту плательщика в формате user@mail.ru',
            ],
        ]);

        $fb->add('paid_at', DateType::class, [
            'label'    => 'Дата оплаты',
            'attr'     => ['class' => 'form-group form-control'],
            'input'    => 'datetime',
            'required' => false,
            'html5'    => true,
            'widget'   => 'single_text',
        ]);

        if($user->hasRole('ROLE_ADMIN')) {
            $fb->add('status', EntityType::class, [
                'label'       => 'Статус',
                'class'       => 'App:Status',
                'constraints' => new Assert\NotBlank(),
                'attr'        => ['class'  => 'form-group form-control'],
            ]);
        }

        if($user->hasRole('ROLE_ADMIN') or $user->hasRole('ROLE_SUPERVISOR')) {
            $fb->add('user', EntityType::class, [
                'label'       => 'Менеджер',
                'class'       => 'App:User',
                'constraints' => new Assert\NotBlank(),
                'attr'        => ['class'  => 'form-group form-control'],
            ]);
        }

        $fb->add('commentary', TextareaType::class, [
            'label'       => 'Комментарий',
            'required'    => false,
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('edit', SubmitType::class, [
            'label' => 'Изменить',
            'attr'  => ['class' => 'btn btn-success'],
        ]);

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($request->isMethod('post') and $form->isSubmitted()) {
            $title      = $form->get('title')->getData();
            $phone      = $form->get('phone')->getData();
            $email      = $form->get('email')->getData();
            $number     = $form->get('number')->getData();
            $paid_at    = $form->get('paid_at')->getData();
            $commentary = $form->get('commentary')->getData();
            $user       = $user->hasRole('ROLE_MANAGER')? $user : $form->get('user')->getData();

            if ($form->isValid()) {
                $invoice->setTitle($title);
                $invoice->setPhone($phone);
                $invoice->setEmail($email);
                $invoice->setNumber($number);
                $invoice->setPaidAt($paid_at);
                $invoice->setCommentary($commentary);
                $invoice->setUser($user);

                if($user->hasRole('ROLE_ADMIN')) {
                    $invoice = $form->get('status')->getData();
                }

                $em->persist($invoice);
                $em->flush();

                $this->addFlash('success', 'Счёт был успешно отредактирован');
                return $this->redirectToRoute('invoice_list');
            }
        }

        return $this->render('invoice/item.html.twig', [
            'form'   => $form->createView(),
            'action' => 'edit',
        ]);
    }

    /**
     * @Route("/delete-{id}/", name="invoice_delete")
     */
    public function deleteAction(Request $request)
    {
        $id   = $request->get('id');
        $em   = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */
        $user = $this->getUser(); /** @var $user \App\Entity\User */

        if($user->hasRole('ROLE_ADMIN')) {
        /** @var $invoice \App\Entity\Invoice */
            $invoice = $em->getRepository('App:Invoice')->createQueryBuilder('i')
                ->andWhere('i.id = :id')->setParameter('id', $id)
                ->setMaxResults(1)->getQuery()->getOneOrNullResult();
            ;

            if (!$invoice) {
                throw $this->createNotFoundException('Not found');
            }

            $em->remove($invoice);
            $em->flush();

            $this->addFlash('success', 'Счёт был успешно удалён');
        }


        if($user->hasRole('ROLE_SUPERVISOR')) {
            /** @var $invoice \App\Entity\Invoice */
            $invoice = $em->getRepository('App:Invoice')->createQueryBuilder('i')
                ->andWhere('i.id = :id')->setParameter('id', $id)
                ->leftJoin('i.status','s')->addSelect('s')
                ->andWhere('s.title IN (:titles)')->setParameter('titles', ['Не оплачен', 'Неудачный'])
                ->setMaxResults(1)->getQuery()->getOneOrNullResult();
            ;

            if (!$invoice) {
                throw $this->createNotFoundException('Not found');
            }

            $status  = $em->getRepository('App:Status')->findOneBy(['title' => 'Удалённый']);

            if (!$status) {
                throw $this->createNotFoundException('Not found');
            }

            $invoice->setStatus($status);
            $em->persist($invoice);
            $em->flush();

            $this->addFlash('success', 'Счёт был успешно удалён');

        }





        if ($request->headers->get('referer')) {
               return $this->redirect($request->headers->get('referer'));
        }

        return $this->redirectToRoute('invoice_list');
    }

    /**
     * @Route("/view-{id}/", name="invoice_view")
     */
    public function viewAction(Request $request)
    {
        $id   = $request->get('id');
        $em   = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */
        $user = $this->getUser(); /** @var $user \App\Entity\User */

        /** @var $invoice \App\Entity\Invoice */
        $qb = $em->getRepository('App:Invoice')->createQueryBuilder('i')
            ->andWhere('i.id = :id')->setParameter('id' ,$id)
        ;

        if($user->hasRole('ROLE_MANAGER')) {
            $qb->leftJoin('i.status','s')->addSelect('s');
            $qb->andWhere('s.title != :title')->setParameter('title', 'Удалённый');
            $qb->andWhere('i.user = :user')->setParameter('user', $user);
        }

        if($user->hasRole('ROLE_SUPERVISOR')) {
            $qb->leftJoin('i.status','s')->addSelect('s');
            $qb->andWhere('s.title != :title')->setParameter('title', 'Удалённый');
        }

        $invoice = $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();

        if (!$invoice) {
            throw $this->createNotFoundException('Not found');
        }

        return $this->render('invoice/view.html.twig', [
            'invoice' => $invoice,
        ]);
    }
}