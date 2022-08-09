<?php

namespace App\Controller;

use App\Entity\Category;
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
        $productsInCart = array();

        if (!$cart) {
            $entityManager = $doctrine->getManager();

            $cart = new ShoppingCart();
            $cart->setUser($user);
            $cart->setProducts([]);

            $entityManager->persist($cart);
            $entityManager->flush();
        }

        $campaignCategory = $doctrine->getRepository(Category::class)->findOneBy(['slug' => 'erkek-ayakkabi']);

        foreach ($products as $product) {
            $allProducts[$product->getId()] = [
                "name" => $product->getName(),
                "price" => $product->getPrice(),
                "category" => $product->getCategory()
            ];
        }

        $cartProducts = $cart->getProducts();

        $cheapProduct = array_reduce($cartProducts, function ($a, $b) {
            return $a && $a['price'] < $b['price'] ? $a : $b;
        });

        foreach ($cartProducts as $key => $value) {
            $totalPrice = 0;
            $totalDiscount = 0;
            $price = 0;

            $name = $allProducts[$value["id"]]["name"];
            $quantity = $value["quantity"];
            $unitPrice = $allProducts[$value["id"]]["price"];
            $categories = $allProducts[$value["id"]]["category"];
            $description = "";

            if ($categories->contains($campaignCategory) && $quantity >= 3) {
                $price += ($quantity - 1) * $unitPrice;
                $totalDiscount +=  $unitPrice;
                $description = "Buy 3 get 1 free!";
            } else {
                if (count($cartProducts) == 1) {
                    if ($quantity > 1) {
                        $price += ($quantity - 1) * $unitPrice;
                        $price += $unitPrice / 2;
                        $totalDiscount +=  $unitPrice / 2;
                        $description = "2nd product 50% off!";
                    } else {
                        $price += $quantity * $unitPrice;
                    }
                } elseif (count($cartProducts) > 1) {
                    if ($value == $cheapProduct && $quantity > 1) {
                        $price += ($quantity - 1) * $unitPrice;
                        $price += $unitPrice / 2;
                        $totalDiscount +=  $unitPrice / 2;
                        $description = "2nd product 50% off!";
                    } else {
                        $price += $quantity * $unitPrice;
                    }
                }
            }

            $totalPrice += $quantity * $unitPrice;

            array_push($productsInCart, array(
                "name" => $name,
                "quantity" => $quantity,
                "unitPrice" => $unitPrice,
                "totalPrice" => $totalPrice,
                "totalDiscount" => $totalDiscount,
                "price" => $price,
                "description" => $description

            ));
        }

        return $this->render('shopping_cart/index.html.twig', [
            'productsInCart' => $productsInCart
        ]);
    }

    #[Route('/profile/cart', name: 'app_shopping_cart_add', methods: ["POST"])]
    public function add(ManagerRegistry $doctrine, Request $request)
    {
        $entityManager = $doctrine->getManager();

        $user = $this->security->getUser();
        $cart = $user->getShoppingCart();

        if (!$cart) {
            $entityManager = $doctrine->getManager();

            $cart = new ShoppingCart();
            $cart->setUser($user);
            $cart->setProducts([]);

            $entityManager->persist($cart);
            $entityManager->flush();
        }

        $products = $cart->getProducts();

        array_push($products, array(
            'id' => $request->get('product'),
            'quantity' => $request->get('quantity'),
            'price' => $request->get('price')
        ));

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

        array_splice($products, $request->get("index"), 1);

        $cart->setProducts($products);
        $entityManager->flush();

        return $this->json([$products, $request->get('index')]);
    }
}
