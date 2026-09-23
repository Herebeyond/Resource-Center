<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig');
    }

    #[Route('/connexion', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(): Response
    {
        return $this->render('account/login.html.twig');
    }

    #[Route('/profil', name: 'app_profile')]
    public function profile(): Response
    {
        return $this->render('account/profile.html.twig');
    }
}
