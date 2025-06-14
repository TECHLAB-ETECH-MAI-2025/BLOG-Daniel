<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api/category')]
class CategoryApiController extends AbstractController
{
    #[Route('/data', name: 'api_category_data', methods: ['POST'])]
    public function datatable(Request $request, CategoryRepository $categoryRepository): JsonResponse
    {
        $draw = $request->request->getInt('draw');
        $start = $request->request->getInt('start');
        $length = $request->request->getInt('length');
        $search = $request->request->all('search')['value'] ?? null;

        $results = $categoryRepository->findForDataTable($start, $length, $search);

        $data = array_map(fn($category) => [
            'id' => $category->getId(),
            'title' => $category->getTitle(),
            'description' => $category->getDescription(),
            'createdAt' => $category->getCreatedAt()?->format('Y-m-d H:i'),
            'actions' => $this->renderView('category/_actions.html.twig', [
                'category' => $category,
            ]),
        ], $results['data']);

        return new JsonResponse([
            'draw' => $draw,
            'recordsTotal' => $results['totalCount'],
            'recordsFiltered' => $results['filteredCount'],
            'data' => $data
        ]);
    }


    #[Route('', name: 'api_category_list', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findAll();

        $data = array_map(fn($category) => $this->serializeCategory($category), $categories);

        return $this->json($data);
    }

    #[Route('/{id}', name: 'api_category_show', methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        return $this->json($this->serializeCategory($category));
    }

    #[Route('', name: 'api_category_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['title'], $data['description'])) {
            return $this->json(['error' => 'Missing fields'], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setTitle($data['title']);
        $category->setDescription($data['description']);
        $category->setCreatedAt(new \DateTime());

        $em->persist($category);
        $em->flush();

        return $this->json($this->serializeCategory($category), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_category_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Category $category, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $category->setTitle($data['title']);
        }

        if (isset($data['description'])) {
            $category->setDescription($data['description']);
        }

        $em->flush();

        return $this->json($this->serializeCategory($category));
    }

    #[Route('/{id}', name: 'api_category_delete', methods: ['DELETE'])]
    public function delete(Category $category, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($category);
        $em->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    private function serializeCategory(Category $category): array
    {
        return [
            'id' => $category->getId(),
            'title' => $category->getTitle(),
            'description' => $category->getDescription(),
            'createdAt' => $category->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
