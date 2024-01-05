<?php

namespace App\EventListener;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    public function __construct(
		private EntityManagerInterface $em
	) { }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
	{
        // Get the User entity.
        $user = $event->getAuthenticationToken()->getUser();

        // Update your field here.
        $user->setLastLogin(new DateTime());

        // Persist the data to database.
        $this->em->persist($user);
        $this->em->flush();
    }
}
