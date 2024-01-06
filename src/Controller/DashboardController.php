<?php

namespace App\Controller;

use App\Repository\ServiceUsageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(ServiceUsageRepository $serviceUsageRepository): Response
	{
		$username = $this->getUser()->getUsername();
		$api_key = $this->getUser()->getApiKey();
		$api_daily_limit = $this->getParameter('api.daily_limit');
		$api_usage = $serviceUsageRepository->getDailyUsage($this->getUser())->getUsage();

        return $this->render('account/dashboard.html.twig', [
			'username' => $username,
			'api_key' => $api_key,
			'api_daily_limit' => $api_daily_limit,
			'api_usage' => $api_usage
		]);
    }
}
