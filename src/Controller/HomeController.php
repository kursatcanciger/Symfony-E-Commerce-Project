<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
  #[Route('/', name: 'main')]
  public function main()
  {
    return $this->redirectToRoute('home');
  }

  #[Route('/products/{slug}', name: 'home', requirements: ['slug' => '.*'])]
  public function index(ManagerRegistry $doctrine, string $slug = null): Response
  {
    $category = null;
    $categoriesArray = [];

    if ($slug) {
      $categories = explode('/', $slug);
      $categoriesArray = $categories;
      $categorySlug = array_pop($categories);


      $category = $doctrine->getRepository(Category::class)->findOneBy(['slug' => $categorySlug]);

      $products = $category->getProducts();
    }

    if (!$slug) $products = $doctrine->getRepository(Product::class)->findAll();

    $categories = $doctrine->getRepository(Category::class)->findBy(['ParentCategory' => $category]);

    return $this->render('home_controller/index.html.twig', [
      'categories' => $categories,
      'categoriesArray' => $categoriesArray,
      'products' => $products
    ]);
  }
}
