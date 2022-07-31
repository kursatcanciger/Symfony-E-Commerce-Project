<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category', methods: ["GET"])]
    public function index(ManagerRegistry $doctrine): Response
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();

        return $this->render('category/index.html.twig', ['categories' => $categories]);
    }

    #[Route('/category/create', name: 'app_category_create', methods: ["GET"])]
    public function create(ManagerRegistry $doctrine): Response
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();
        return $this->render('category/create.html.twig', ['categories' => $categories]);
    }

    #[Route('/category/{slug}', name: 'app_category_show', methods: ["GET"])]
    public function show(ManagerRegistry $doctrine, string $slug): Response
    {
        $category = $doctrine->getRepository(Category::class)->findOneBy(['slug' => $slug]);

        return $this->render('category/show.html.twig', ['category' => $category]);
    }

    #[Route('/category/create', name: 'app_category_store', methods: ["POST"])]
    public function store(ManagerRegistry $doctrine, ValidatorInterface $validator, Request $request): Response
    {
        $entityManager = $doctrine->getManager();

        $category = new Category();
        $category->setSlug($request->get('slug'));
        $category->setName($request->get('name'));

        if ($request->get('parent')) {
            $parent = $doctrine->getRepository(Category::class)->find($request->get('parent'));
            $category->setParentCategory($parent);
        }

        $errors = $validator->validate($category);

        if (count($errors) > 0) {
            return $this->render('category/create.html.twig', ['errors' => $errors]);
        }

        $entityManager->persist($category);

        $entityManager->flush();

        return $this->redirectToRoute('app_category');
    }

    #[Route('/category/{slug}/edit', name: 'app_category_edit', methods: ["GET"])]
    public function edit(ManagerRegistry $doctrine, string $slug): Response
    {
        $category = $doctrine->getRepository(Category::class)->findOneBy(['slug' => $slug]);
        $categories = $doctrine->getRepository(Category::class)->findAll();

        return $this->render('category/edit.html.twig', ['category' => $category, 'categories' => $categories]);
    }

    #[Route('/category/{slug}', name: 'app_category_update', methods: ["POST"])]
    public function update(ManagerRegistry $doctrine, Request $request, string $slug): Response
    {
        $entityManager = $doctrine->getManager();

        $category = $doctrine->getRepository(Category::class)->findOneBy(['slug' => $slug]);
        $category->setSlug($request->get('category_slug'));
        $category->setName($request->get('name'));

        if ($request->get('parent')) {
            $parent = $doctrine->getRepository(Category::class)->find($request->get('parent'));
            $category->setParentCategory($parent);
        }

        $entityManager->persist($category);

        $entityManager->flush();

        return $this->redirectToRoute('app_category');
    }

    #[Route('/category/{id}', name: 'app_category_destroy', methods: ["DELETE"])]
    public function destroy(ManagerRegistry $doctrine, int $id): Response
    {
        $entityManager = $doctrine->getManager();

        $category = $doctrine->getRepository(Category::class)->find($id);

        $entityManager->remove($category);
        $entityManager->flush();

        return $this->json([], 200);
    }
}
