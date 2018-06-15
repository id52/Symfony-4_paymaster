<?php
namespace App\Controller;

use App\Entity\User;
use Symfony\Component\Form\FormError;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Pagerfanta;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/** @Route("/users") */
class UserController extends Controller
{

    /**
     * @Route("/")
     * @Route("/list/", name="admin_user_list")
     */
    public function listAction(Request $request)
    {
        /** @var $em \Doctrine\ORM\EntityManager */
        $em = $this->container->get('doctrine')->getManager();
        $qb = $em->getRepository('App:User')->createQueryBuilder('u');

        $pagerfanta = new Pagerfanta(new DoctrineORMAdapter($qb));
        $pagerfanta->setMaxPerPage(20);
        $pagerfanta->setCurrentPage($request->get('page', 1));

        $roles = [
            'ROLE_ADMIN'      => 'Администратор',
            'ROLE_SUPERVISOR' => 'Супервайзер',
            'ROLE_MANAGER'    => 'Менеджер',
        ];

        return $this->render('user/list.html.twig', [
            'pagerfanta' => $pagerfanta,
            'roles'      => $roles,
        ]);
    }

    /**
     * @Route("/create/", name="admin_user_create")
     */
    public function createAction(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $em = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */

        $roleChoices = [
            'Менеджер'      => 'ROLE_MANAGER',
            'Супервайзер'   => 'ROLE_SUPERVISOR',
            'Администратор' => 'ROLE_ADMIN',
        ];

        $fb = $this->createFormBuilder();

        $fb->add('username', TextType::class, [
            'label'       => 'Логин',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('email', EmailType::class, [
            'label'       => 'Электронная почта',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('second_name', TextType::class, [
            'label'       => 'Фамилия',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('first_name', TextType::class, [
            'label'       => 'Имя',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('patronymic', TextType::class, [
            'label'       => 'Отчество',
            'required'    => false,
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('role', ChoiceType::class, [
            'label'       => 'Роль',
            'placeholder' => ' - Выберите роль - ',
            'choices'     => $roleChoices,
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('password', RepeatedType::class, [
            'type'            => PasswordType::class,
            'invalid_message' => 'Пароли должны совпадать',
            'label'           => 'Пароль снова',
            'options'         => ['attr' => ['class' => 'password-field']],
            'required'        => true,
            'first_options'   => [
                'label' => 'Пароль',
                'attr'  => ['class' => 'form-group form-control', 'autocomplete' => 'off'],
            ],
            'second_options'  => [
                'label' => 'Пароль снова',
                'attr'  => ['class' => 'form-group form-control', 'autocomplete' => 'off'],
            ],
            'constraints'     => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
        ]);

        $fb->add('add', SubmitType::class, [
            'label' => 'Добавить',
            'attr'  => ['class' => 'btn btn-success mt-4'],
        ]);

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($request->isMethod('post') and $form->isSubmitted()) {
            $email       = $form->get('email')->getData();
            $username    = $form->get('username')->getData();
            $first_name  = $form->get('first_name')->getData();
            $second_name = $form->get('second_name')->getData();
            $patronymic  = $form->get('patronymic')->getData();
            $password    = $form->get('password')->getData();
            $role        = $form->get('role')->getData();

            $existed_user = $em->getRepository('App:User')->findBy(['email' => $email]);
            if ($existed_user) {
                $form->get('email')->addError(
                    new FormError('Уже есть пользователь с такой электронной почтой')
                );
            }

            $existed_user = $em->getRepository('App:User')->findBy(['username' => $username]);
            if ($existed_user) {
                $form->get('username')->addError(
                    new FormError('Уже есть пользователь с таким именем пользователя')
                );
            }

            if ($form->isValid()) {
                $user = new User();
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setFirstName($first_name);
                $user->setSecondName($second_name);
                $user->setPatronymic($patronymic);
                $user->setRoles([$role]);

                $encoded_password = $encoder->encodePassword($user, $password);
                $user->setPassword($encoded_password);

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Пользователь успешно добавлен');
                return $this->redirectToRoute('admin_user_list');
            }
        }

        return $this->render('user/item.html.twig', [
            'form'   => $form->createView(),
            'action' => 'add',
        ]);

    }

    /**
     * @Route("/edit-{id}/", name="admin_user_edit")
     */
    public function editAction(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $id = $request->get('id');
        $em = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */

        $user = $em->getRepository('App:User')->find($id); /** @var $user \App\Entity\User */
        if (!$user) {
            throw $this->createNotFoundException('Not found');
        }

        $role  = $user->getRoles()[0];

        $data = [
            'first_name'  => $user->getFirstName(),
            'second_name' => $user->getSecondName(),
            'patronymic'  => $user->getPatronymic(),
            'username'   => $user->getUsername(),
            'email'      => $user->getEmail(),
            'role'       => $role,
        ];

        $roleChoices = [
            'Менеджер'      => 'ROLE_MANAGER',
            'Супервайзер'   => 'ROLE_SUPERVISOR',
            'Администратор' => 'ROLE_ADMIN',
        ];

        $fb = $this->createFormBuilder($data);

        $fb->add('username', TextType::class, [
            'label'       => 'Логин',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('email', EmailType::class, [
            'label'       => 'Электронная почта',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('second_name', TextType::class, [
            'label'       => 'Фамилия',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('first_name', TextType::class, [
            'label'       => 'Имя',
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('patronymic', TextType::class, [
            'label'       => 'Отчество',
            'required'    => false,
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('role', ChoiceType::class, [
            'label'       => 'Роль',
            'placeholder' => ' - Выберите роль - ',
            'choices'     => $roleChoices,
            'constraints' => new Assert\NotBlank(['message' => 'Поле не должно быть пустым']),
            'attr'        => ['class' => 'form-group form-control'],
        ]);

        $fb->add('password', RepeatedType::class, [
            'type'            => PasswordType::class,
            'invalid_message' => 'Пароли должны совпадать',
            'label'           => 'Пароль снова',
            'options'         => ['attr' => ['class' => 'password-field']],
            'required'        => false,
            'first_options'   => [
                'label' => 'Пароль',
                'attr'  => ['class' => 'form-group form-control', 'autocomplete' => 'off'],
            ],
            'second_options'  => [
                'label' => 'Пароль снова',
                'attr'  => ['class' => 'form-group form-control', 'autocomplete' => 'off'],
            ],
        ]);

        $fb->add('edit', SubmitType::class, [
            'label' => 'Изменить',
            'attr'  => ['class' => 'btn btn-success'],
        ]);

        $form = $fb->getForm();
        $form->handleRequest($request);
        if ($request->isMethod('post') and $form->isSubmitted()) {
            $email       = $form->get('email')->getData();
            $username    = $form->get('username')->getData();
            $first_name  = $form->get('first_name')->getData();
            $second_name = $form->get('second_name')->getData();
            $patronymic  = $form->get('patronymic')->getData();
            $password    = $form->get('password')->getData();
            $role        = $form->get('role')->getData();

            $existed_user = $em->getRepository('App:User')->createQueryBuilder('u')
                ->andWhere('u.email = :email')->setParameter('email', $email)
                ->andWhere('u.id != :id')->setParameter('id', $id)
                ->getQuery()->getOneOrNullResult();

            if ($existed_user) {
                $form->get('email')->addError(new FormError('Уже есть пользователь с такой электронной почтой'));
            }

            $existed_user = $em->getRepository('App:User')->createQueryBuilder('u')
                ->andWhere('u.username = :username')->setParameter('username', $username)
                ->andWhere('u.id != :id')->setParameter('id', $id)
                ->getQuery()->getOneOrNullResult();

            if ($existed_user) {
                $form->get('username')->addError(new FormError('Уже есть пользователь с таким именем пользователя'));
            }

            if ($form->isValid()) { /** @var $user \App\Entity\User */
                $user->setUsername($username);
                $user->setEmail($email);
                $user->setFirstName($first_name);
                $user->setSecondName($second_name);
                $user->setPatronymic($patronymic);
                $user->setRoles([$role]);

                if ($password) {
                    $encoded_password = $encoder->encodePassword($user, $password);
                    $user->setPassword($encoded_password);
                }

                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Пользователь был успешно отредактирован');
                return $this->redirectToRoute('admin_user_list');
            }
        }

        return $this->render('user/item.html.twig', [
            'form'   => $form->createView(),
            'action' => 'edit',
        ]);
    }

    /**
     * @Route("/delete-{id}/", name="admin_user_delete")
     */
    public function deleteAction(Request $request)
    {
        $id = $request->get('id');
        $em = $this->container->get('doctrine')->getManager(); /** @var $em \Doctrine\ORM\EntityManager */
        $user = $this->getUser(); /** @var $user \App\Entity\User */
        $user->getId();

        if ($id and $id != $user->getId()) {
            $user = $em->getRepository('App:User')->find($id);
            if (!$user) {
                throw $this->createNotFoundException('Not found');
            }

            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Пользователь был удалён');
        }
        return $this->redirect($request->headers->get('referer'));
    }


}