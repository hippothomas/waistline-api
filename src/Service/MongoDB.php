<?php

namespace App\Service;

use Exception;
use MongoDB\Collection;
use MongoDB\Client as Mongo;
use Psr\Log\LoggerInterface;

readonly class MongoDB
{
    public function __construct(
		private string          $mongoDbUrl,
		private string          $mongoDbName,
		private LoggerInterface $logger
	) { }

	/**
	 * Retrieves a MongoDB collection or returns false on failure
	 * @return Collection|false The MongoDB collection or false if the connection fails.
	 */
    private function getCollection(): Collection|false
	{
		// Create a new MongoDB client instance
		$mongo = new Mongo($this->mongoDbUrl);

		// Test the connection to the MongoDB server and return the collection
		try {
			$mongo->{$this->mongoDbName}->command(['ping' => 1]);
			return $mongo->{$this->mongoDbName}->journal;
		} catch (Exception $e) {
			$this->logger->error('[MongoDB] Exception: {exception}', [
				'exception' => $e->getMessage(),
			]);
		}
		return false;
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
}
