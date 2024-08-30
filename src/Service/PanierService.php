<?php

namespace App\Service;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class PanierService
{

    public function addToCart(SessionInterface $session, Product $product): void
    {
        $cart = $session->get('cart', []);
        $productId = $product->getId();

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity']++;
        } else {
            $cart[$productId] = [
                'product' => $product,
                'quantity' => 1,
            ];
        }

        $session->set('cart', $cart);
    }

    public function removeFromCart(SessionInterface $session, Product $product): void
    {
        $cart = $session->get('cart', []);
        $productId = $product->getId();

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }

        $session->set('cart', $cart);
    }

    public function getCartItems(SessionInterface $session): array
    {
        return $session->get('cart', []);
    }

    public function getTotal(SessionInterface $session): float
    {
        $total = 0;
        foreach ($this->getCartItems($session) as $item) {
            $total += $item['product']->getPrice() * $item['quantity'];
        }

        return $total;
    }
}
