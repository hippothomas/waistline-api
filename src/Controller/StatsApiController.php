<?php

namespace App\Controller;

use App\Service\MongoDB;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

class StatsApiController extends ApiController
{
	private const array FIELD_POSSIBILITIES = [
		"calories",
		"kilojoules",
		"fat",
		"saturated-fat",
		"carbohydrates",
		"sugars",
		"fiber",
		"proteins",
		"salt",
		"sodium"
	];

    public function __construct(
		private readonly MongoDB $mongoDB
	) { }

	#[Route('/api/v1/stats', name: 'api_stats', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
		$user = $this->getUser();

		// TODO: Add filters (start_date / interval / period)

		// Handle partial response with parameter 'fields'
		$fields_parameter = (string) $request->get('fields');

		// TODO: Add sorting

		$cursor = $this->mongoDB->retrieveUserNutritionData(
			$user->getId(),
			[ '2024-01-05', '2024-01-06' ],
			$this->getFields($fields_parameter)
		);
		if ($cursor === false) {
			throw new HttpException(500, 'An error occurred while processing your request. Please try again later.');
		}

		$results = [];
		foreach ($cursor as $r) {
			$date = DateTime::createFromFormat('Y-m-d\TH:i:s.000\Z', $r["_id"]);
			$results[$date->format('Y-m-d')] = (array) $r["data"];
		}

        return new JsonResponse($this->formatResults($results), Response::HTTP_OK);
    }

	/**
	 * Retrieves fields from request parameters based on field possibilities
	 * @param string $fields_parameter Fields passed in request parameters
	 * @return array
	 */
	private function getFields(string $fields_parameter): array
	{
		return array_filter(
			self::FIELD_POSSIBILITIES,
			fn($possibility) => str_contains($fields_parameter, $possibility)
		);
	}
}
