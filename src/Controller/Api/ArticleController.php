<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\ArticleLike;
use App\Entity\Comment;
use App\Form\CommentForm;
use App\Repository\ArticleRepository;
use App\Repository\ArticleLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class ArticleController extends AbstractController{
    #[Route('/articles/data', name: 'api_articles_data', methods: ['POST'])]
    public function datatable(Request $request, ArticleRepository $articleRepository): JsonResponse
    { 
        $draw = $request->request->getInt('draw');
        $start = $request->request->getInt('start');
        $length = $request->request->getInt('length');
        $search = $request->request->all('search')['value'] ?? null;
        $orders = $request->request->all('order') ?? [];

        $columns = [
            0 => 'a.id',
            1 => 'a.title',
            2 => 'categories',
            3 => 'commentsCount',
            4 => 'likesCount',
            5 => 'a.createdAt',
        ];

        $orderColumn = $columns[$orders[0]['column'] ?? 0] ?? 'a.id';
        $orderDir = $orders[0]['dir'] ?? 'desc';

        $results = $articleRepository->findForDataTable($start, $length, $search, $orderColumn, $orderDir);

        $data = [];
        foreach ($results['data'] as $article) {
            $categoryNames = array_map(fn($category) => $category->getTitle(), $article->getCategories()->toArray());

            $data[] = [
                'id' => $article->getId(),
                'title' => sprintf('<a href="%s">%s</a>',
                    $this->generateUrl('app_article_show', ['id' => $article->getId()]),
                    htmlspecialchars($article->getTitle())
                ),
                'categories' => implode(' ', $categoryNames),
                'commentsCount' => $articleRepository->countCommentsForArticle($article),
                'likesCount' => $article->getLikes()->count(),
                'createdAt' => $article->getCreatedAt()->format('d/m/Y H:i'),
                'actions' => $this->renderView('article/_actions.html.twig', [
                    'article' => $article
                ])
            ];
        }

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $results['totalCount'],
            'recordsFiltered' => $results['filteredCount'],
            'data' => $data
        ]);
     }

    #[Route('/articles/search', name: 'api_articles_search', methods: ['GET'])]
    public function search(Request $request, ArticleRepository $articleRepository): JsonResponse 
    {  $query = $request->query->get('q', '');

        if (strlen($query) < 2) {
            return new JsonResponse(['results' => []]);
        }

        $articles = $articleRepository->searchByTitle($query, 10);

        $results = [];
        foreach ($articles as $article) {
            $categoryNames = array_map(fn($category) => $category->getTitle(), $article->getCategories()->toArray());

            $results[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'categories' => $categoryNames
            ];
        }

        return new JsonResponse(['results' => $results]);
    }

    #[Route('/article/{id}/like', name: 'api_article_like', methods: ['POST'])]
    public function likeArticle(Article $article, Request $request, EntityManagerInterface $entityManager, ArticleLikeRepository $likeRepository): JsonResponse 
    {
        $ipAddress = $request->getClientIp();

        $existingLike = $likeRepository->findOneBy([
            'article' => $article,
            'ipAddress' => $ipAddress
        ]);

        if ($existingLike) {
            $entityManager->remove($existingLike);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'liked' => false,
                'likesCount' => $article->getLikes()->count()
            ]);
        } else {
            $like = new ArticleLike();
            $like->setArticle($article);
            $like->setIpAddress($ipAddress);
            $like->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($like);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'liked' => true,
                'likesCount' => $article->getLikes()->count()
            ]);
        } 
    }

    
}