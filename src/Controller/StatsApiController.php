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
	private const array FILTER_POSSIBILITIES = [
		"lt" => '$lt', // Less than
		"gt" => '$gt', // Greater than
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
		// Set $date_from to current date -6 day or to the request parameter date
		$date_from = $this->formatDateParameter((string) $request->get('date_from'), '-6 day');
		// Set $date_to to current date +1 day or to the request parameter date
		$date_to = $this->formatDateParameter((string) $request->get('date_to'), '+1 day');
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

		// Handle sorting (DESC by Default)
		$sort = match ((string) $request->get('sort')) {
			'ASC' => 1,
    		default => -1
		};

		// Handle pagination
		$limit = (int) $request->get('limit', 100);
		// Maximum allowed limit value is 500
		if ($limit > 500) {
			throw new HttpException(400, 'Invalid limit value. The maximum allowed limit is 500.');
		}
		$offset = (int) $request->get('offset', 0);
		// Verify if both limit and offset have valid values
		if ($limit <= 0 || $offset < 0) {
			throw new HttpException(400, 'Invalid limit or offset value. Limit must be greater than 0, and offset must be non-negative.');
		}

		// Handle filtering
		$filters_parameter = (string) $request->get('filter');

		$cursor = $this->mongoDB->retrieveUserNutritionData(
			$user->getId(),
			$date_list,
			$this->getFields($fields_parameter),
			$sort,
			$limit,
			$offset,
			$this->getFilters($filters_parameter),
		);
		if ($cursor === false) {
			throw new HttpException(500, 'An error occurred while processing your request. Please try again later.');
		}

		// Retrieve the current user nutrition data from the cursor
		$cursor->next();
		$user_nutrition_data = $cursor->current();
		if ($user_nutrition_data === false) {
			throw new HttpException(500, 'An error occurred while processing your request. Please try again later.');
		}

		// Format the request result
		$results = [];
		foreach ($user_nutrition_data["paginatedResults"] as $r) {
			$date = DateTime::createFromFormat(DateTimeInterface::RFC3339_EXTENDED, $r["_id"]);
			if ($date === false) continue; // Date is not compatible with format DateTimeInterface::RFC3339_EXTENDED
			$results[$date->format('Y-m-d')] = (array) $r["data"];
		}

		// Format pagination
		$total_count = $user_nutrition_data["totalCount"][0]["count"] ?? 0;
		$pagination = [
			"limit" => $limit,
			"offset" => $offset,
			"total" => $total_count
		];

        return new JsonResponse($this->formatResults($results, $pagination), Response::HTTP_OK);
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

	/**
	 * Get the filters list base on request parameters
	 * @param string $filters_parameter Filters passed in request parameters
	 * @return array
	 */
	private function getFilters(string $filters_parameter): array
	{
		$filters = [];

		// Get filter list
		foreach (explode(',', $filters_parameter) as $param) {
			// Check if the filter pattern is respected : field[operator]:number
			if (!preg_match('/([A-Za-z]+)\\[([A-Za-z0-9]+)]:([0-9]+)/i', $param, $matches)) {
				continue;
			}
			// Check if the field is valid
			if (!in_array($matches[1], self::FIELD_POSSIBILITIES)) {
				continue;
			}
			// Check if the operator is valid
			if (!array_key_exists($matches[2], self::FILTER_POSSIBILITIES)) {
				continue;
			}
			// Check if the last parameter is a valid number
			if (!is_numeric($matches[3])) {
				continue;
			}
			$filters["nutrition.".$matches[1]][self::FILTER_POSSIBILITIES[$matches[2]]] = (int) $matches[3];
		}
		return $filters;
	}
}
