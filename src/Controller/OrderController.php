<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class OrderController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/profile/checkout', name: 'app_checkout', methods: ["GET"])]
    public function checkout(ManagerRegistry $doctrine): Response
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

        return $this->render('order/checkout.html.twig', [
            'cart' => $cart,
            'products' => $allProducts
        ]);
    }

    #[Route('/profile/checkout', name: 'app_checkout_apply', methods: ["POST"])]
    public function checkout_post(Request $request, ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();

        $user = $this->security->getUser();
        $cart = $user->getShoppingCart();
        $products = $doctrine->getRepository(Product::class)->findAll();
        $allProducts = array();
        $productsInCart = array();

        foreach ($products as $product) {
            $allProducts[$product->getId()] = [
                "name" => $product->getName(),
                "price" => $product->getPrice()
            ];
        }

        $totalPrice = 0;
        $discount = 0;

        foreach ($cart->getProducts() as $key => $value) {
            $totalPrice += $value["quantity"] * $allProducts[$value["id"]]["price"];

            array_push($productsInCart, [
                "quantity" => $value["quantity"],
                "name" => $allProducts[$value["id"]]["name"],
                "price" => $allProducts[$value["id"]]["price"]
            ]);
        }

        $address = $request->get("f_name") . " " . $request->get("l_name") . PHP_EOL . $request->get("address");

        $order = new Order();
        $order->setUser($user);
        $order->setProducts($productsInCart);
        $order->setTotalPrice($totalPrice);
        $order->setDiscount($discount);
        $order->setPrice($totalPrice - $discount);
        $order->setAddress($address);
        $order->setStatus("Pending");

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->json($productsInCart);
    }

    #[Route('/admin/order', name: 'app_order', methods: ["GET"])]
    public function index(ManagerRegistry $doctrine): Response
    {
        $orders = $doctrine->getRepository(Order::class)->findAll();

        return $this->render('order/index.html.twig', [
            'orders' => $orders
        ]);
    }
}
