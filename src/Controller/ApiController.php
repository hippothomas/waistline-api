<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiController extends AbstractController
{
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
}
