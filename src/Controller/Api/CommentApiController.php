<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\Comment;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/comment')]
class CommentApiController extends AbstractController
{
    #[Route('/data', name: 'api_comment_data', methods: ['POST'])]
    public function datatable(Request $request, CommentRepository $commentRepository): JsonResponse
    {
        $draw = $request->request->getInt('draw');
        $start = $request->request->getInt('start');
        $length = $request->request->getInt('length');
        $search = $request->request->all('search')['value'] ?? null;

        $results = $commentRepository->findForDataTable($start, $length, $search);

        $data = array_map(function (Comment $comment) {
            return [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()?->format('Y-m-d H:i'),
                'articleTitle' => $comment->getArticle()->getTitle(),
                'user' => $comment->getUser()?->getUserIdentifier(),
                'actions' => $this->renderView('comment/_actions.html.twig', [
                    'comment' => $comment,
                ]),
            ];
        }, $results['data']);

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $results['totalCount'],
            'recordsFiltered' => $results['filteredCount'],
            'data' => $data,
        ]);
    }

    #[Route('', name: 'api_comment_list', methods: ['GET'])]
    public function index(CommentRepository $commentRepository): JsonResponse
    {
        $comments = $commentRepository->findAll();

        $data = array_map(fn($comment) => $this->serializeComment($comment), $comments);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_comment_show', methods: ['GET'])]
    public function show(Comment $comment): JsonResponse
    {
        return $this->json($this->serializeComment($comment));
    }

    #[Route('/new/{articleId}', name: 'api_comment_create', methods: ['POST'])]
    public function create(
        int $articleId,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $article = $em->getRepository(Article::class)->find($articleId);

        if (!$article) {
            return $this->json(['error' => 'Article not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['content']) || empty(trim($data['content']))) {
            return $this->json(['error' => 'Content is required'], Response::HTTP_BAD_REQUEST);
        }

        $comment = new Comment();
        $comment->setContent($data['content']);
        $comment->setCreatedAt(new \DateTimeImmutable());
        $comment->setArticle($article);
        $comment->setUser($this->getUser());

        $em->persist($comment);
        $em->flush();

        return $this->json($this->serializeComment($comment), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_comment_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Comment $comment, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            $comment->setContent($data['content']);
        }

        $em->flush();

        return $this->json($this->serializeComment($comment));
    }

    #[Route('/{id}', name: 'api_comment_delete', methods: ['DELETE'])]
    public function delete(Comment $comment, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($comment);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeComment(Comment $comment): array
    {
        return [
            'id' => $comment->getId(),
            'content' => $comment->getContent(),
            'createdAt' => $comment->getCreatedAt()?->format('Y-m-d H:i:s'),
            'article' => [
                'id' => $comment->getArticle()->getId(),
                'title' => $comment->getArticle()->getTitle(),
            ],
            'user' => $comment->getUser()?->getUserIdentifier(),
        ];
    }
}
