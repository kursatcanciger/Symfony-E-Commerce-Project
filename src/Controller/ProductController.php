<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ProductController extends AbstractController
{
    #[Route('/admin/product', name: 'app_product', methods: ["GET"])]
    public function index(ManagerRegistry $doctrine): Response
    {
        $products = $doctrine->getRepository(Product::class)->findAll();

        return $this->render('product/index.html.twig', ['products' => $products]);
    }

    #[Route('/admin/product/create', name: 'app_product_create', methods: ["GET"])]
    public function create(ManagerRegistry $doctrine): Response
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();

        return $this->render('product/create.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/admin/product/{slug}', name: 'app_product_show', methods: ["GET"])]
    public function show(ManagerRegistry $doctrine, string $slug): Response
    {
        $product = $doctrine->getRepository(Product::class)->findOneBy(['slug' => $slug]);

        return $this->render('product/show.html.twig', ['product' => $product]);
    }

    #[Route('/admin/product/create', name: 'app_product_store', methods: ["POST"])]
    public function store(ManagerRegistry $doctrine, Request $request, ValidatorInterface $validator): Response
    {
        $entityManager = $doctrine->getManager();

        $product = new Product();
        $product->setSlug($request->get('slug'));
        $product->setName($request->get('name'));
        $product->setPrice($request->get('price'));

        foreach ($request->get('categories') as $id) {
            $category = $doctrine->getRepository(Category::class)->find($id);

            $product->addCategory($category);
        }

        $errors = $validator->validate($product);

        if (count($errors) > 0) {
            $categories = $doctrine->getRepository(Category::class)->findAll();
            return $this->render('product/create.html.twig', ['categories' => $categories, 'errors' => $errors]);
        }

        $entityManager->persist($product);

        $entityManager->flush();

        return $this->redirectToRoute('app_product');
    }

    #[Route('/admin/product/{slug}/edit', name: 'app_product_edit', methods: ["GET"])]
    public function edit(ManagerRegistry $doctrine, string $slug): Response
    {
        $categories = $doctrine->getRepository(Category::class)->findAll();
        $product = $doctrine->getRepository(Product::class)->findOneBy(['slug' => $slug]);

        return $this->render('product/edit.html.twig', ['product' => $product, 'categories' => $categories]);
    }

    #[Route('/admin/product/{slug}', name: 'app_product_update', methods: ["POST"])]
    public function update(ManagerRegistry $doctrine, Request $request, string $slug): Response
    {
        $entityManager = $doctrine->getManager();

        $product = $doctrine->getRepository(Product::class)->findOneBy(['slug' => $slug]);

        $product->setSlug($request->get('product_slug'));
        $product->setName($request->get('name'));
        $product->setPrice($request->get('price'));

        foreach ($product->getCategory() as $category) {
            $product->removeCategory($category);
        }

        foreach ($request->get('categories') as $id) {
            $category = $doctrine->getRepository(Category::class)->find($id);

            $product->addCategory($category);
        }

        $entityManager->persist($product);

        $entityManager->flush();

        return $this->redirectToRoute('app_product');
    }

    #[Route('/admin/product/{id}', name: 'app_product_destroy', methods: ["delete"])]
    public function destroy(ManagerRegistry $doctrine, int $id): Response
    {
        $entityManager = $doctrine->getManager();

        $product = $doctrine->getRepository(Product::class)->find($id);

        $entityManager->remove($product);
        $entityManager->flush();

        return $this->json([], 200);
    }
}
