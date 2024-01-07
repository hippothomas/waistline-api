<?php

namespace App\Controller;

use App\Service\MongoDB;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class SyncApiController extends AbstractController
{
    public function __construct(
		private readonly MongoDB $mongoDB
	) { }

	#[Route('/api/v1/sync', name: 'api_sync', methods: ['POST'])]
    public function index(Request $request): JsonResponse
    {
		$user = $this->getUser();
		$body = $request->getContent();

		// Check if there was an error during the JSON decoding process and if the decoded data is not null
		$data = json_decode($body, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new HttpException(400, 'Invalid JSON format.');
		}
		if ($data === null) {
			throw new HttpException(400, 'Invalid JSON data.');
		}

		// Add user & timestamp in data
		$data['user_id'] = $user->getId();
		$data['timestamp'] = time();

		// Insert data in MongoDB
		$result = $this->mongoDB->insertOne($data);
		if (!$result) {
			throw new HttpException(500, 'An error occurred while processing your request. Please try again later.');
		}

        return new JsonResponse([
			"status" => 200,
			"message" => "Data synchronized."
		], Response::HTTP_OK);
    }
}
