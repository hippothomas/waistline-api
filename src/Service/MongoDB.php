<?php

namespace App\Service;

use DateTime;
use Exception;
use MongoDB\BSON\Regex;
use MongoDB\Collection;
use MongoDB\Client as Mongo;
use MongoDB\Database;
use MongoDB\Driver\CursorInterface;
use Psr\Log\LoggerInterface;

readonly class MongoDB
{
    public function __construct(
		private string          $mongoDbUrl,
		private string          $mongoDbName,
		private LoggerInterface $logger
	) { }

	/**
	 * Retrieves a MongoDB database or returns false on failure
	 * @return Database|false The MongoDB database or false if the connection fails.
	 */
	private function getDb(): Database|false
	{
		// Create a new MongoDB client instance
		$mongo = new Mongo($this->mongoDbUrl);

		// Test the connection to the MongoDB server and return the database
		try {
			$mongo->{$this->mongoDbName}->command(['ping' => 1]);
			return $mongo->{$this->mongoDbName};
		} catch (Exception $e) {
			$this->logger->error('[MongoDB] Exception: {exception}', [
				'exception' => $e->getMessage(),
			]);
		}
		return false;
	}

	/**
	 * Retrieves a MongoDB collection or returns false on failure
	 * @return Collection|false The MongoDB collection or false if the connection fails.
	 */
    private function getCollection(): Collection|false
	{
		return $this->getDb()?->journal ?? false;
    }

	/**
	 * Check if an actual connection to the database is established
	 * @return bool
	 */
	public function isConnected(): bool
	{
		return $this->getDb() !== false;
	}

	/**
	 * Inserts a single document into the MongoDB collection
	 * @param array $data The data to be inserted into the collection.
	 * @return bool Returns true on successful insertion, false otherwise.
	 */
	public function insertOne(array $data): bool
	{
		$collection = $this->getCollection();
		if (!$collection) return false;

		// Insert data into the collection and return true, if successfully inserted
		try {
			$insertOneResult = $collection->insertOne($data);
			if ($insertOneResult->getInsertedCount() === 1) {
				return true;
			}
		} catch (Exception $e) {
			$this->logger->error('[MongoDB] Exception: {exception}', [
				'exception' => $e->getMessage(),
			]);
		}
		return false;
	}

	/**
	 * Finds a single document by user ID and date
	 * @param int $user_id User ID to search for
	 * @param DateTime $date Date to search for
	 * @return array|false Returns the result as an array if successfully found, false otherwise.
	 */
	public function findOne(int $user_id, DateTime $date): array|false
	{
		$collection = $this->getCollection();
		if (!$collection) return false;

		// Search into the collection and return the result as an array, if successfully found
		try {
			$result = $collection->findOne([
				'user_id' => $user_id,
				'entry.dateTime' => new Regex("{$date->format('Y-m-d')}.*")
			],
			[
				'projection' => [
					'nutrition' => 1,
					'entryDetails' => 1,
					'entry' => 1,
				],
				'sort' => ['timestamp' => -1],
				'limit' => 1
			]);
			return (array) $result;
		} catch (Exception $e) {
			$this->logger->error('[MongoDB] Exception: {exception}', [
				'exception' => $e->getMessage(),
			]);
		}
		return false;
	}

	/**
	 * Retrieve nutrition data based on request parameters
	 * @param int   $user_id      	User ID to search for
	 * @param array $date_list    	List of dates
	 * @param array $fields_group  	List of fields to filter the mongo request
	 * @param int 	$sort			Specify a sorting order
	 * @param int 	$limit			Specify a pagination limit
	 * @param int 	$offset			Specify a pagination offset value
	 * @param array $filters_group  List filter for the mongo request
	 * @return CursorInterface|false Returns the retrieved nutrition data, false otherwise
	 */
	public function retrieveUserNutritionData(int $user_id, array $date_list, array $fields_group, int $sort = -1, int $limit = 100, int $offset = 0, array $filters_group = []): CursorInterface|false
	{
		// Build query for the View
		$aggregate_query = $this->getNutritionAggregateQuery();

		// Check if the view exist otherwise create it
		$view = $this->getView("nutrition", "journal", $aggregate_query);
		if (!$view) return false;

		// Prepare the query
		$match = ['user_id' => $user_id];

		// Prepare the date list for the request
		if (empty($date_list)) return false;
		$dates = [];
		foreach ($date_list as $date) {
			$dates[] = [ 'dateTime' => new Regex("{$date}.*") ];
		}
		$match['$or'] = $dates;

		// Prepare the fields for the request
		$fields = [];
		if (!empty($fields_group)) {
			foreach ($fields_group as $nutrition) {
				$fields[$nutrition] = '$$ROOT.nutrition.'.$nutrition;
			}
		}

		// Add filters for the request
		$match = array_merge($match, $filters_group);

		// Search into the view and return results as an array, if successfully found
		try {
			return $view->aggregate([
				[
					'$match' => $match
				],
				[
					'$group' => [
						'_id' => '$dateTime',
						'data' => [
							'$first' => !empty($fields) ? $fields : '$$ROOT.nutrition'
						]
					]
				],
				[
					'$sort' => [ '_id' => $sort ]
				],
				[
					'$facet' => [
					  	'paginatedResults' => [
							[ '$skip' => $offset ],
							[ '$limit' => $limit ]
					  	],
					  	'totalCount' => [
							['$count' => 'count']
					  	]
					]
				]
			]);
		} catch (Exception $e) {
			$this->logger->error('[MongoDB] Exception: {exception}', [
				'exception' => $e->getMessage(),
			]);
		}
		return false;
	}

	/**
	 * Get or create a MongoDB view
	 * @param  string $view_name 		The name of the MongoDB view
	 * @param  string $collection_name 	The name of the collection on which the view is based
	 * @param  array  $aggregate_query 	An array representing the aggregation pipeline to create the view
	 * @return Collection|false Returns the MongoDB view (Collection) if it exists or was successfully created. Returns false on failure
	 */
	private function getView(string $view_name, string $collection_name = "", array $aggregate_query = []): Collection|false
	{
		$db = $this->getDb();
		if (!$db) return false;

		// Check if the view exist
		$collections = $db->listCollections();
		foreach ($collections as $collection) {
			if ($collection->getType() === "view" && $collection->getName() === $view_name) {
				return $db->{$view_name};
			}
		}
		if (empty($aggregate_query) || empty($collection_name)) return false;

		// Create the view if it doesn't exist
		try {
			$db->command([
				'create' => $view_name,
				'viewOn' => $collection_name,
				'pipeline' => $aggregate_query
			]);
		} catch (Exception $e) {
			$this->logger->error('[MongoDB] Exception: {exception}', [
				'exception' => $e->getMessage(),
			]);
			return false;
		}
		return $this->getView($view_name);
	}

	private function getNutritionAggregateQuery(): array
	{
		return [
			[
				'$sort' => [ 'timestamp' => -1 ]
			],
			[
				'$group' => [
					'_id' => [
						'user_id' => '$user_id',
						'dateTime' => '$entry.dateTime'
					],
					'nutrition' => [
						'$first' => '$$ROOT.nutrition'
					],
					'dateTime' => [
						'$first' => '$$ROOT.entry.dateTime'
					],
					'user_id' => [
						'$first' => '$$ROOT.user_id'
					]
				]
			]
		];
	}
}
