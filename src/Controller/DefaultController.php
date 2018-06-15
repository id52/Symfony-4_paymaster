<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Security;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     * @Route("/", name="default")
     */
    public function defaultAction(Request $request, AuthenticationUtils $authUtils)
    {
      return $this->redirectToRoute('invoice_list');
    }
}