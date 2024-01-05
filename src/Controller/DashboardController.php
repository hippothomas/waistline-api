<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'dashboard')]
    public function index(): Response
	{
        $username = $this->getUser()->getUsername();
        return $this->render('account/dashboard.html.twig', [
			'username' => $username
		]);
    }
}
