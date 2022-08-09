<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\ShoppingCart;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class ShoppingCartController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/profile/cart', name: 'app_shopping_cart', methods: ["GET"])]
    public function index(ManagerRegistry $doctrine): Response
    {
        $user = $this->security->getUser();
        $cart = $user->getShoppingCart();
        $products = $doctrine->getRepository(Product::class)->findAll();
        $allProducts = array();

        foreach ($products as $product) {
            $allProducts[$product->getId()] = [
                "name" => $product->getName(),
                "price" => $product->getPrice()
            ];
        }

        if (!$cart) {
            $entityManager = $doctrine->getManager();

            $cart = new ShoppingCart();
            $cart->setUser($user);
            $cart->setProducts([]);

            $entityManager->persist($cart);
            $entityManager->flush();
        }

        return $this->render('shopping_cart/index.html.twig', [
            'cart' => $cart,
            'products' => $allProducts
        ]);
    }

    #[Route('/profile/cart', name: 'app_shopping_cart_add', methods: ["POST"])]
    public function add(ManagerRegistry $doctrine, Request $request)
    {
        $entityManager = $doctrine->getManager();

        $user = $this->security->getUser();
        $cart = $user->getShoppingCart();

        $products = $cart->getProducts();

        array_push($products, [
            'id' => $request->get('product'),
            'quantity' => $request->get('quantity'),
        ]);

        $cart->setProducts($products);
        $entityManager->flush();

        return $this->json($products);
    }


    #[Route('/profile/cart', name: 'app_shopping_cart_remove', methods: ["DELETE"])]
    public function remove(ManagerRegistry $doctrine, Request $request)
    {
        $entityManager = $doctrine->getManager();

        $user = $this->security->getUser();
        $cart = $user->getShoppingCart();

        $products = $cart->getProducts();

        unset($products[$request->get('index')]);

        $cart->setProducts($products);
        $entityManager->flush();

        return $this->json([$products, $request->get('index')]);
    }
}
