<?php

namespace App\Controller;

use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
    public function __construct(
		private readonly LoggerInterface $logger
	) { }

	/**
	 * Formats an array of results
	 * @param array $results The results to format
	 * @param bool  $single  Whether to ensure the result is a single-item array
	 * @return array The formatted results
	 */
    protected function formatResults(array $results, bool $single = false): array
    {
		if (!empty($results) && $single) {
			$results = [ $results ];
		}
        $content = ["results" => $results];
        $content["count"] = count($content["results"]);
        return $content;
    }

	/**
	 * Formats a date parameter into a DateTime object.
	 * @param string $date The date parameter
	 * @param string $default A date/time string. Valid formats are explained in {@link https://php.net/manual/en/datetime.formats.php Date and Time Formats}.
	 * @return DateTime Return a DateTime of the formatted date or the current date, if parsing fails
	 */
	protected function formatDateParameter(string $date, string $default = 'now'): DateTime
	{
		$datetime = DateTime::createFromFormat('Y-m-d', $date);
		// If an invalid Date/Time string is passed in $default, DateMalformedStringException is thrown
		try {
			return $datetime !== false ? $datetime : new DateTime($default);
		} catch (Exception $e) {
			$this->logger->warning('[ApiController][DateTime] Exception: {exception}', [
				'exception' => $e->getMessage(),
			]);
		}
		return new DateTime();
	}
}
