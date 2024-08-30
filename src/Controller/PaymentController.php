<?php

namespace App\Controller;

use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    /**
     * @throws ApiErrorException
     */
    #[Route('/create-checkout-session', name: 'create_checkout_session')]
    public function createCheckoutSession(SessionInterface $session): Response
    {
        // Initialize Stripe
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        // Get cart items from session
        $cart = $session->get('cart', []);

        // Prepare line items for Stripe
        $lineItems = [];
        foreach ($cart as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['product']->getName(),
                    ],
                    'unit_amount' => $item['product']->getPrice() * 100
                ],
                'quantity' => $item['quantity'],
            ];
        }

        // Create Stripe checkout session
        $checkoutSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => [$lineItems],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new JsonResponse(['id' => $checkoutSession->id]);
    }

    #[Route('/paiement-success', name: 'payment_success')]
    public function paymentSuccess(SessionInterface $session): Response
    {
        $session->set('cart', []);

        return $this->render('payment/success.html.twig');
    }

    #[Route('/paiement-annulation', name: 'payment_cancel')]
    public function paymentCancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}
