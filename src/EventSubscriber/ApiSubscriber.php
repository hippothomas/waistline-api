<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Repository\ServiceUsageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Uid\Uuid;

class ApiSubscriber implements EventSubscriberInterface
{
    public function __construct(
		private readonly int 					$apiDailyLimit,
		private readonly EntityManagerInterface $em,
		private readonly ServiceUsageRepository $serviceUsageRepository,
		private readonly LoggerInterface        $logger,
		private readonly Security 				$security
	) { }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Verify if we are in an API context
        if (str_starts_with($path, "/api")) {
			// Check if the incoming HTTP request lacks the 'Authorization' header
			if (!$request->headers->has('Authorization')) {
				throw new HttpException(401, 'You have not supplied an API Key.');
			}

			// Check if the 'Authorization' header starts with the expected prefix "ApiKey "
			$authorization = $request->headers->get('Authorization');
			if (!str_starts_with($authorization, "ApiKey ")) {
				throw new HttpException(401, 'You have not supplied an API Key.');
			}

			// Check if the extracted API Key is a valid UUID
			$authorization = explode(" ", $authorization)[1];
			if (!Uuid::isValid($authorization)) {
				throw new HttpException(401, 'Your API Key is not valid.');
			}

			// Search the user based on the API Key
			$user = $this->em->getRepository(User::class)->findOneBy(['apiKey' => $authorization]);
			if (empty($user)) {
				throw new HttpException(401, 'Your API Key is not valid.');
			}

			// Check if the API rate limit is exceeded
			$serviceUsage = $this->serviceUsageRepository->getDailyUsage($user);
			if ($serviceUsage->getUsage() > $this->apiDailyLimit) {
				$this->logger->info('User {userId} has exceeded his API rate limit.', [
					'userId' => $user->getId(),
				]);
				throw new HttpException(429, 'Daily API rate limit exceeded.');
			}
			// Increment the current call to the daily usage
			$this->serviceUsageRepository->upsertDailyUsage($serviceUsage);

			// Log the user in on the current firewall, so it can be used in the controller
			$this->security->login($user);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
