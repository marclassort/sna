<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\PanierService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class RouteController extends AbstractController
{
    public function __construct(private readonly PanierService $cartService)
    {
    }

    #[Route("/", name: "app_home")]
    public function getHome(): Response
    {
        return $this->render("home/home.html.twig");
    }
    
    #[Route("/ryu", name: "app_ryu")]
    public function getRyu(): Response
    {
        return $this->render("home/ryu.html.twig");
    }

    #[Route("/arts-martiaux", name: "app_arts_martiaux")]
    public function getArtsMartiaux(): Response
    {
        return $this->render("home/arts-martiaux.html.twig");
    }

    #[Route("/arts-culturels", name: "app_arts_culturels")]
    public function getArtsCulturels(): Response
    {
        return $this->render("home/arts-culturels.html.twig");
    }

    #[Route("/vertus", name: "app_vertus")]
    public function getVertus(): Response
    {
        return $this->render("home/vertus.html.twig");
    }

    #[Route("/equipe", name: "app_equipe")]
    public function getEquipe(): Response
    {
        return $this->render("home/equipe.html.twig");
    }

    #[Route("/evenements", name: "app_evenements")]
    public function getEvenements(): Response
    {
        return $this->render("home/evenements.html.twig");
    }

    #[Route("/galerie", name: "app_galerie")]
    public function getGalerie(): Response
    {
        return $this->render("home/galerie.html.twig");
    }

    #[Route("/boutique", name: "app_boutique")]
    public function getBoutique(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy(
            [],
            [
                "id" => "DESC"
            ]);

        return $this->render("home/boutique.html.twig",
            [
                "products" => $products
            ]
        );
    }

    #[Route("/produit", name: "app_produit")]
    public function getProduit(): Response
    {
        return $this->render("home/produit.html.twig");
    }

    #[Route("/panier", name: "app_panier")]
    public function getPanier(SessionInterface $session): Response
    {
        $items = $this->cartService->getCartItems($session);
        $total = $this->cartService->getTotal($session);

        return $this->render("home/panier.html.twig", [
            "items" => $items,
            "total" => $total,
            'stripe_public_key' => $this->getParameter('stripe_public_key')
        ]);
    }

    #[Route('/cart/add/{id}', name: 'cart_add')]
    public function add(SessionInterface $session, Product $product): Response
    {
        $this->cartService->addToCart($session, $product);
        return $this->redirectToRoute('app_panier');
    }

    #[Route('/cart/update/ajax', name: 'cart_update_ajax', methods: ['POST'])]
    public function updateAjax(SessionInterface $session, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $productId = $data['id'];
        $quantity = (int) $data['quantity'];

        $cart = $session->get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $quantity;
        }

        $session->set('cart', $cart);

        $itemTotal = $cart[$productId]['product']->getPrice() * $quantity;
        $cartTotal = $this->cartService->getTotal($session);

        return new JsonResponse([
            'success' => true,
            'itemTotal' => number_format($itemTotal, 2),
            'cartTotal' => number_format($cartTotal, 2),
        ]);
    }

    #[Route('/cart/remove/{id}', name: 'cart_remove')]
    public function remove(SessionInterface $session, Product $product): Response
    {
        $this->cartService->removeFromCart($session, $product);
        return $this->redirectToRoute('app_panier');
    }

    #[Route("/paiement", name: "app_paiement")]
    public function getPaiement(): Response
    {
        return $this->render("home/paiement.html.twig");
    }

    #[Route("/inscription-club", name: "app_inscription_club")]
    public function getInscription(): Response
    {
        return $this->render("home/inscription-club.html.twig");
    }

    #[Route("/inscription-individuelle", name: "app_inscription_individuelle")]
    public function getInscriptionIndividuelle(): Response
    {
        return $this->render("home/inscription-individuelle.html.twig");
    }

    #[Route("/inscription-arts-culturels", name: "app_inscription_arts_culturels")]
    public function getInscriptionArtsCulturels(): Response
    {
        return $this->render("home/inscription-arts-culturels.html.twig");
    }

    #[Route("/contact", name: "app_contact")]
    public function getContact(): Response
    {
        return $this->render("home/contact.html.twig");
    }

    #[Route("/politique-de-confidentialite", name: "app_politique_de_confidentialite")]
    public function getPrivacyPolicy(): Response
    {
        return $this->render("home/politique-de-confidentialite.html.twig");
    }
}