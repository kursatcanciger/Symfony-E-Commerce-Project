<?php

namespace App\Controller;

use App\Entity\Category;
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

    if ($slug) {
      $categories = explode('/', $slug);
      $categorySlug = array_pop($categories);

      $category = $doctrine->getRepository(Category::class)->findBy(['slug' => $categorySlug]);
    }

    $categories = $doctrine->getRepository(Category::class)->findBy(['ParentCategory' => $category]);

    return $this->render('home_controller/index.html.twig', ['categories' => $categories]);
  }
}
