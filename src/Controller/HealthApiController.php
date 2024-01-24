<?php

namespace App\Controller;

use App\Service\MongoDB;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HealthApiController extends AbstractController
{
    public function __construct(
		private readonly EntityManagerInterface $em,
		private readonly MongoDB 				$mongoDB,
		private readonly LoggerInterface        $logger,
	) { }

	#[Route('/api/v1/health', name: 'api_health', methods: ['GET'])]
    public function index(): JsonResponse
    {
		$healthy = true;

		// Check filesystem available space (100 KiB mini.)
		$bytes = disk_free_space(".");
		if ($bytes === false || $bytes < (1024 * 100)) {
			$this->logger->warning('[HealthApiController][filesystem] There is less than 100KiB available on the filesystem.');
			$healthy = false;
		}

		// Check Doctrine connection
		try {
			// Try to establish the connection
			$this->em->getConnection()->connect();
		} catch (Exception $e) {
			$this->logger->alert('[HealthApiController][Doctrine] Exception: {exception}', [
				'exception' => $e->getMessage(),
			]);
			$healthy = false;
		}
		// Check the connection
		if (!$this->em->getConnection()->isConnected()) {
			$this->logger->alert('[HealthApiController][Doctrine] Error while checking connection to the database.');
			$healthy = false;
		}

		// Check MongoDB connection
		if (!$this->mongoDB->isConnected()) {
			$this->logger->alert('[HealthApiController][MongoDB] Error while checking connection to the database.');
			$healthy = false;
		}

        return new JsonResponse([
			"healthy" => $healthy
		], Response::HTTP_OK);
    }
}
