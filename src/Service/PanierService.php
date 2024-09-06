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

    public function addClubRegistrationToCart(SessionInterface $session, int $memberCount, ?string $csvFilePath = null): void
    {
        $cart = $session->get('cart', []);

        $clubPrice = 50.00;

        $cart['club'] = [
            'price' => $clubPrice,
            'members' => $memberCount,
            'csv' => $csvFilePath,
        ];

        $session->set('cart', $cart);
    }

    public function addIndividualRegistrationToCart(SessionInterface $session): void
    {
        $cart = $session->get('cart', []);
        $cart['registrations']['individual'] = [
            'price' => 10.00,
        ];

        $session->set('cart', $cart);
    }

    public function addCulturalRegistrationToCart(SessionInterface $session): void
    {
        $cart = $session->get('cart', []);
        $cart['registrations']['cultural'] = [
            'price' => 20.00,
        ];

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
        $cart = $this->getCartItems($session);

        if (isset($cart['club']['price'])) {
            $total += $cart['club']['price'];
            $total += $cart['club']['members'] * 5;
        }

        foreach ($cart as $key => $item) {
            if (isset($item['product'])) {
                $total += $item['product']->getPrice() * $item['quantity'];
            }

            if (isset($item['type']) && $item['type'] === 'inscription_individuelle') {
                $total += $item['price'];
            }
        }

        return $total;
    }

    public function resetClubRegistration(SessionInterface $session): void
    {
        $cart = $session->get('cart', []);

        if (isset($cart['club'])) {
            unset($cart['club']);
        }

        $session->remove('club_name');
        $session->remove('logo');
        $session->remove('email');
        $session->remove('president_name');
        $session->remove('treasurer_name');
        $session->remove('address');
        $session->remove('address2');
        $session->remove('zip');
        $session->remove('city');
        $session->remove('country');
        $session->remove('csv');

        $session->set('cart', $cart);
    }
}
