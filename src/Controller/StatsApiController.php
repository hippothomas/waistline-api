<?php

namespace App\Controller;

use App\Service\MongoDB;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeInterface;
use Exception;
use Psr\Log\LoggerInterface;
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
	private const array INTERVAL_POSSIBILITIES = [
		"day" => "P1D",
		"week" => "P1W",
		"month" => "P1M",
		"year" => "P1Y"
	];

    public function __construct(
		private readonly MongoDB $mongoDB,
		private readonly LoggerInterface $logger
	) {
		parent::__construct($logger);
	}

	#[Route('/api/v1/stats', name: 'api_stats', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
		$user = $this->getUser();

		// Handle filters with parameters 'date_from' / 'date_to' / 'interval'
		// Set $date_from to current date or to the request parameter date
		$date_from = $this->formatDateParameter((string) $request->get('date_from'));
		// Set $date_to to current date + 1 week or to the request parameter date
		$date_to = $this->formatDateParameter((string) $request->get('date_to'), '+1 week');
		// Check if the interval parameter is contained in possibility list or set a day interval
		$date_interval = $this->getDateInterval((string) $request->get('interval'));

		// Check if the start date is greater than the end date
		if ($date_from > $date_to) {
			throw new HttpException(400, 'Invalid date range. The start date cannot be greater than the end date.');
		}

		// Generate the date list based on filters
		$date_list = [];
		$period = new DatePeriod($date_from, $date_interval, $date_to);
		foreach ($period as $datetime) {
			$date_list[] = $datetime->format('Y-m-d');
		}

		// Limit the list to prevent db issues
		if (count($date_list) > 500) {
			throw new HttpException(400, 'Too many dates requested. Please consider using a larger interval or narrowing down the date range.');
		}

		// Handle partial response with parameter 'fields'
		$fields_parameter = (string) $request->get('fields');

		// TODO: Add sorting
		$sort = 'ASC';

		// TODO: Add pagination
		$limit = 100;

		$cursor = $this->mongoDB->retrieveUserNutritionData(
			$user->getId(),
			$date_list,
			$this->getFields($fields_parameter)
		);
		if ($cursor === false) {
			throw new HttpException(500, 'An error occurred while processing your request. Please try again later.');
		}

		$results = [];
		foreach ($cursor as $r) {
			$date = DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $r["_id"]);
			if ($date === false) {
				$this->logger->warning('[StatsApiController][DateTime] Date: {date} is not compatible with format DateTimeInterface::RFC3339_EXTENDED.', [
					'date' => (string) $r["_id"]
				]);
				throw new HttpException(500, 'An error occurred while processing your request. Please contact support.');
			}
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

	/**
	 * Gets a DateInterval from interval parameter based on interval possibilities
	 * @param string $interval_parameter Interval passed in request parameters
	 * @return DateInterval
	 */
	private function getDateInterval(string $interval_parameter): DateInterval
	{
		// Search the interval in the possibility list
		if (array_key_exists($interval_parameter, self::INTERVAL_POSSIBILITIES)) {
			try {
				return new DateInterval(self::INTERVAL_POSSIBILITIES[$interval_parameter]);
			} catch (Exception $e) {
				$this->logger->warning('[StatsApiController][DateInterval] Exception: {exception}', [
					'exception' => $e->getMessage(),
				]);
			}
		}
		// In case of exception or if not found in possibility list return day interval
		return new DateInterval('P1D');
	}
}
