<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AccountController extends AbstractController
{
    #[Route('/login', name: 'account_login')]
    public function index(AuthenticationUtils $utils): Response
    {
        $error = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();

        return $this->render('account/login.html.twig', [
            'hasError' => $error !== null,
            'username' => $username
        ]);
    }

    #[Route('/logout', name: 'account_logout')]
    public function logout(): void
    {
        // Handled by Symfony
    }

    #[Route('/register', name: 'account_register')]
    public function register(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $entityManager): Response
    {
		$register = $this->getParameter('app.register');

        if ($register) {
			$user = new User();
			$form = $this->createForm(RegistrationFormType::class, $user);
			$form->handleRequest($request);

			if ($form->isSubmitted() && $form->isValid()) {
				$user->setPassword(
					$hasher->hashPassword(
						$user,
						$user->getPassword()
					)
				);

				$entityManager->persist($user);
				$entityManager->flush();

				$this->addFlash(
                    "success",
                    "Your account has been created! You can now log in!"
                );

				return $this->redirectToRoute('account_login');
			}

			return $this->render('account/register.html.twig', [
				'form' => $form->createView(),
			]);
        } else {
            $this->addFlash(
                "warning",
                "The account registration is closed!"
            );

            return $this->redirectToRoute("account_login");
        }
    }
}
