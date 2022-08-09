<?php

namespace App\Controller;

use App\Entity\Category;
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
        $productsInCart = array();

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


        return $this->render('order/checkout.html.twig', [
            'productsInCart' => $productsInCart
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
        $discount = $request->get("discount");

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

        $cart->setProducts([]);

        $entityManager->flush();

        return $this->redirectToRoute('app_user_order');
    }

    #[Route('/profile/order', name: 'app_user_order', methods: ["GET"])]
    public function user_order(): Response
    {
        $user = $this->security->getUser();
        $orders = $user->getOrders();
        return $this->render('order/user_order.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/admin/order', name: 'app_order', methods: ["GET"])]
    public function index(ManagerRegistry $doctrine): Response
    {
        $orders = $doctrine->getRepository(Order::class)->findAll();

        return $this->render('order/index.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/admin/order/{id}', name: 'app_order_edit', methods: ["GET"])]
    public function edit(ManagerRegistry $doctrine, int $id): Response
    {
        $order = $doctrine->getRepository(Order::class)->findOneBy(["id" => $id]);
        return $this->render('order/edit.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/admin/order/{id}', name: 'app_order_update', methods: ["POST"])]
    public function update(ManagerRegistry $doctrine, Request $request, int $id): Response
    {
        $entityManager = $doctrine->getManager();

        $order = $doctrine->getRepository(Order::class)->findOneBy(["id" => $id]);
        $order->setStatus($request->get('status'));

        $entityManager->flush();

        return $this->redirectToRoute('app_order');
    }
}
