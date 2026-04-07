<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/users')]
final class UserAdminController extends AbstractController
{
    #[Route('', name: 'admin_users')]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin/user/index.html.twig', [
            'users' => $userRepository->findBy([], ['id' => 'ASC']),
        ]);
    }

    #[Route('/nouveau', name: 'admin_users_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'password_required' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->get('plainPassword')->getData();
            $user->setPassword($passwordHasher->hashPassword($user, $plain));
            $user->setCreatedAt(new \DateTimeImmutable());
            $user->setUpdatedAt(new \DateTimeImmutable());
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur créé.');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/form.html.twig', [
            'form' => $form,
            'title' => 'Nouvel utilisateur',
        ]);
    }

    #[Route('/{id}/modifier', name: 'admin_users_edit', requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        User $targetUser,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $form = $this->createForm(UserType::class, $targetUser, [
            'password_required' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plain = $form->has('plainPassword') ? $form->get('plainPassword')->getData() : null;
            if (\is_string($plain) && $plain !== '') {
                if (strlen($plain) < 6) {
                    $this->addFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');

                    return $this->render('admin/user/form.html.twig', [
                        'form' => $form,
                        'title' => 'Modifier utilisateur',
                    ]);
                }
                $targetUser->setPassword($passwordHasher->hashPassword($targetUser, $plain));
            }
            $targetUser->setUpdatedAt(new \DateTimeImmutable());
            $em->flush();
            $this->addFlash('success', 'Utilisateur mis à jour.');

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/user/form.html.twig', [
            'form' => $form,
            'title' => 'Modifier utilisateur',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'admin_users_delete', methods: ['POST'])]
    public function delete(Request $request, User $targetUser, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$targetUser->getId(), (string) $request->request->get('_token'))) {
            $me = $this->getUser();
            if ($me instanceof User && $me->getId() === $targetUser->getId()) {
                $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');

                return $this->redirectToRoute('admin_users');
            }
            $em->remove($targetUser);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé.');
        }

        return $this->redirectToRoute('admin_users');
    }
}
