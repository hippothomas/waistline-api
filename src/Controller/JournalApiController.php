<?php

namespace App\Controller;

use App\Service\MongoDB;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class JournalApiController extends ApiController
{
    public function __construct(
		private readonly MongoDB $mongoDB,
		private readonly LoggerInterface $logger
	) {
		parent::__construct($logger);
	}

	#[Route('/api/v1/journal', name: 'api_journal', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
		$user = $this->getUser();

		// Set $date to current date or to the request parameter date
		$date = $this->formatDateParameter((string) $request->get('date'));

		// Get data from MongoDB
		$result = $this->mongoDB->findOne($user->getId(), $date);
		if ($result === false) {
			throw new HttpException(500, 'An error occurred while processing your request. Please try again later.');
		}

		// Remove user & timestamp in data
		unset($result['_id']);

        return new JsonResponse($this->formatResults($result, [], true), Response::HTTP_OK);
    }
}
