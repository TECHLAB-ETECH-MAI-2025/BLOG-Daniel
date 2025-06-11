<?php

namespace App\Controller\Api;

use App\Entity\Article;
use App\Entity\ArticleLike;
use App\Entity\Comment;
use App\Form\CommentForm;
use App\Repository\ArticleRepository;
use App\Repository\ArticleLikeRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/api/article')]
final class ArticleController extends AbstractController{
    #[Route('', name: 'api_article_list', methods: ['GET'])]
    public function index(ArticleRepository $articleRepository): JsonResponse
    {
        $articles = $articleRepository->findAll();

        $data = array_map(fn($article) => $this->serializeArticle($article), $articles);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_article_show', methods: ['GET'])]
    public function show(Article $article): JsonResponse
    {
        return $this->json($this->serializeArticle($article));
    }

    #[Route('', name: 'api_article_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, CategoryRepository $categoryRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['title'], $data['content'])) {
            return $this->json(['error' => 'Missing fields'], Response::HTTP_BAD_REQUEST);
        }

        $article = new Article();
        $article->setTitle($data['title']);
        $article->setContent($data['content']);
        $article->setCreatedAt(new \DateTimeImmutable());

        // Gérer les catégories si fournies
        if (!empty($data['categories']) && is_array($data['categories'])) {
            foreach ($data['categories'] as $catId) {
                $category = $categoryRepo->find($catId);
                if ($category) {
                    $article->addCategory($category);
                }
            }
        }

        $em->persist($article);
        $em->flush();

        return $this->json($this->serializeArticle($article), Response::HTTP_CREATED);
    }


    #[Route('/{id}', name: 'api_article_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Article $article, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $article->setTitle($data['title']);
        }

        if (isset($data['content'])) {
            $article->setContent($data['content']);
        }

        $em->flush();

        return $this->json($this->serializeArticle($article));
    }

    #[Route('/{id}', name: 'api_article_delete', methods: ['DELETE'])]
    public function delete(Article $article, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($article);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeArticle(Article $article): array
    {
        return [
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'categories' => array_map(fn($category) => $category->getTitle(), $article->getCategories()->toArray()),
            'likesCount' => $article->getLikes()->count(),
            'commentsCount' => count($article->getComments()),
            'createdAt' => $article->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
    }

    #[Route('/data', name: 'api_articles_data', methods: ['POST'])]
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
                'title' => $article->getTitle(),
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

    #[Route('/search', name: 'api_articles_search', methods: ['GET'])]
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

    #[Route('/{id}/like', name: 'api_article_like', methods: ['POST'])]
    public function likeArticle(
        Article $article,
        EntityManagerInterface $entityManager,
        ArticleLikeRepository $likeRepository
    ): JsonResponse {
        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedException('Vous devez être connecté pour liker un article.');
        }
    
        $existingLike = $likeRepository->findOneBy([
            'article' => $article,
            'user' => $user
        ]);
    
        if ($existingLike) {
            $entityManager->remove($existingLike);
            $entityManager->flush();
    
            return new JsonResponse([
                'success' => true,
                'liked' => false,
                'likesCount' => $article->getLikes()->count(),
            ]);
        } else {
            $like = new ArticleLike();
            $like->setArticle($article);
            $like->setUser($user);
            $like->setCreatedAt(new \DateTimeImmutable());
    
            $entityManager->persist($like);
            $entityManager->flush();
    
            return new JsonResponse([
                'success' => true,
                'liked' => true,
                'likesCount' => $article->getLikes()->count(),
            ]);
        }
    }    
}