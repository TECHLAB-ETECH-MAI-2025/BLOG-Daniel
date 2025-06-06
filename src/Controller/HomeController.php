<?php

namespace App\Controller;

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
    public function index(ArticleRepository $articleRepository, CategoryRepository $categoryRepository, PaginatorInterface $paginator,
    Request $request): Response
    {

        $pagination = $paginator->paginate(
            $articleRepository->findBy([],['createdAt' => 'DESC']),
            $request->query->getInt('page', 1),
            10 // articles par page
        );

        return $this->render('home/index.html.twig', [
            'articles' => $pagination,
            'categories' => $categoryRepository->findAll(),
        ]);
    }
}
