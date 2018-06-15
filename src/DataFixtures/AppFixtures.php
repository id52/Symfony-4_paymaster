<?php

namespace App\DataFixtures;

use App\Entity\Invoice;
use App\Entity\User;
use App\Entity\Status;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $status = new Status();
        $status->setTitle('Не оплачен');
        $manager->persist($status);

        $user = new User();
        $user->setUsername('root');
        $user->setFirstName('root');
        $user->setPatronymic('root');
        $user->setSecondName('root');
        $user->setEmail('drcooper@mailinator.com');
        $user->setRoles(['ROLE_ADMIN']);
        $password = $this->encoder->encodePassword($user, 'root');
        $user->setPassword($password);
        $manager->persist($user);

        $user = new User();
        $user->setUsername('admin');
        $user->setFirstName('admin');
        $user->setPatronymic('admin');
        $user->setSecondName('admin');
        $user->setEmail('admin@admin.admin');
        $user->setRoles(['ROLE_ADMIN']);
        $password = $this->encoder->encodePassword($user, 'admin');
        $user->setPassword($password);
        $manager->persist($user);

        $invoice = new Invoice();
        $invoice->setTitle('Тестовый 1');
        $invoice->setNumber('1812835812581252');
        $invoice->setCommentary('Тестовый 1');
        $invoice->setEmail('test1@example.com');
        $invoice->setPhone('+71112223344');
        $invoice->setUser($user);
        $invoice->setStatus($status);
        $invoice->setSum(100);
        $invoice->setUri('http://asdfasdfasf.ru');
        $manager->persist($invoice);

        $user = new User();
        $user->setUsername('supervisor');
        $user->setFirstName('supervisor');
        $user->setPatronymic('supervisor');
        $user->setSecondName('supervisor');
        $user->setEmail('supervisor@supervisor.supervisor');
        $user->setRoles(['ROLE_SUPERVISOR']);
        $password = $this->encoder->encodePassword($user, 'supervisor');
        $user->setPassword($password);
        $manager->persist($user);

        $invoice = new Invoice();
        $invoice->setTitle('Тестовый 2');
        $invoice->setNumber('1812835812581252');
        $invoice->setCommentary('Тестовый 2');
        $invoice->setEmail('test2@example.com');
        $invoice->setPhone('+75552223344');
        $invoice->setUser($user);
        $invoice->setStatus($status);
        $invoice->setSum(200);
        $invoice->setUri('http://asdfasdfasf.ru');
        $manager->persist($invoice);

        $user = new User();
        $user->setUsername('manager');
        $user->setFirstName('manager');
        $user->setPatronymic('manager');
        $user->setSecondName('manager');
        $user->setEmail('manager@manager.manager');
        $user->setRoles(['ROLE_MANAGER']);
        $password = $this->encoder->encodePassword($user, 'manager');
        $user->setPassword($password);
        $manager->persist($user);

        $invoice = new Invoice();
        $invoice->setTitle('Тестовый 3');
        $invoice->setNumber('1812835812581252');
        $invoice->setCommentary('Тестовый 3');
        $invoice->setEmail('test3@example.com');
        $invoice->setPhone('+78882223344');
        $invoice->setUser($user);
        $invoice->setStatus($status);
        $invoice->setSum(500);

        $invoice->setUri('http://asdfasdfasf.ru');
        $manager->persist($invoice);

        $status = new Status();
        $status->setTitle('Оплачен');
        $manager->persist($status);

        $status = new Status();
        $status->setTitle('Неудачный');
        $manager->persist($status);

        $status = new Status();
        $status->setTitle('Удалённый');
        $manager->persist($status);

        $manager->flush();
    }
}