<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\MessageForm;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ArticleRepository $articleRepository,
        CategoryRepository $categoryRepository,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $receiverId = $request->query->getInt('receiver_id', 0);
        $receiver = $receiverId ? $entityManager->getRepository(User::class)->find($receiverId) : null;

        $messages = ($receiver)
            ? $messageRepository->findConversation($currentUser->getId(), $receiver->getId())
            : [];

        $message = new \App\Entity\Message();
        $form = $this->createForm(\App\Form\MessageForm::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $receiver) {
            $message->setSender($currentUser);
            $message->setReceiver($receiver);
            $message->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('app_home', ['receiver_id' => $receiver->getId()]);
        }

        $pagination = $paginator->paginate(
            $articleRepository->findBy([], ['createdAt' => 'DESC']),
            $request->query->getInt('page', 1),
            10
        );

        $allUsers = $entityManager->getRepository(User::class)->findAll();

        return $this->render('home/index.html.twig', [
            'articles' => $pagination,
            'categories' => $categoryRepository->findAll(),
            'messages' => $messages,
            'receiver' => $receiver,
            'form' => $form->createView(),
            'users' => $allUsers,
            'currentUser' => $currentUser,
        ]);
    }

    


}
