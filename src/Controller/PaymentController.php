<?php

namespace App\Controller;

use App\Entity\Club;
use App\Entity\Member;
use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
        Stripe::setApiKey($this->getParameter('stripe_secret_key'));

        $cart = $session->get('cart', []);

        $lineItems = [];

        if (isset($cart['club'])) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Inscription d\'un club',
                    ],
                    'unit_amount' => 5000,
                ],
                'quantity' => 1,
            ];

            $memberCount = $cart['club']['members'] ?? 0;

            if ($memberCount > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Inscription d\'un membre',
                        ],
                        'unit_amount' => 500, // Prix par membre
                    ],
                    'quantity' => $memberCount,
                ];
            }
        }

        foreach ($cart as $key => $item) {
            // Gérer les produits classiques
            if ($key !== 'club' && isset($item['product'])) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $item['product']->getName(),
                        ],
                        'unit_amount' => $item['product']->getPrice() * 100, // Le prix en centimes
                    ],
                    'quantity' => $item['quantity'],
                ];
            }

            // Gérer les inscriptions individuelles
            if (isset($cart['club_individuel'])) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Inscription individuelle',
                        ],
                        'unit_amount' => $item['price'] * 100, // Prix de l'inscription individuelle en centimes
                    ],
                    'quantity' => 1,
                ];
            }

            // Gérer les inscriptions arts culturels
            if (isset($cart['cultural_registration'])) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Inscription arts culturels',
                        ],
                        'unit_amount' => $item['price'] * 100, // Prix de l'inscription individuelle en centimes
                    ],
                    'quantity' => 1,
                ];
            }
        }

        $checkoutSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $this->generateUrl('payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new JsonResponse(['id' => $checkoutSession->id]);
    }

    /**
     * @throws Exception
     */
    #[Route('/paiement-succes', name: 'payment_success')]
    public function paymentSuccess(
        SessionInterface $session,
        EntityManagerInterface $entityManager,
    ): Response
    {
        // Récupération de l'ID de la command
        $orderId = $session->get('orderId');
        if (!$orderId) {
            throw new Exception('No orderId found in session.');
        }

        // Récupération de la commande depuis la base de données
        $order = $entityManager->getRepository(Order::class)->find($orderId);
        if (!$order) {
            throw new Exception('No order found for id ' . $orderId);
        }

        // Récupération des données du panier
        $cart = $session->get('cart');

        $csvFilePath = $cart['club']['csvFilePath'] ?? null;
        if ($csvFilePath) {
            // Récupération des membres à partir du fichier CSV/Excel
            $extension = pathinfo($csvFilePath, PATHINFO_EXTENSION);
            if ($extension === 'csv') {
                $members = $this->getMembersFromCSV($csvFilePath);
            } else {
                $members = $this->getMembersFromXLSX($csvFilePath);
            }

            // Création d'un nouvel objet Club
            $clubData = $cart['club'];
            $club = new Club();

            $logoFile = $clubData["logo"];
            $club->setLogo($logoFile);

            $club->setName($clubData["club_name"]);
            $club->setLogo($clubData["logo"]);
            $club->setEmail($clubData["email"]);
            $club->setPresidentName($clubData["president_name"]);
            $club->setTreasurerName($clubData["treasurer_name"]);
            $club->setAddress($clubData["address"] . ", " . $clubData["address2"]);
            $club->setPostalCode($clubData["zip"]);
            $club->setCity($clubData["city"]);
            $club->setCountry($clubData["country"]);

            $entityManager->persist($club);

            // Création des membres à partir du fichier
            foreach ($members as $data) {
                $member = new Member();
                $member->setFirstName($data[0]);
                $member->setLastName($data[1]);
                $member->setSex($data[2]);
                $member->setBirthDate($data[3]);
                $member->setAddress($data[4] ?? null);
                $member->setPostalCode($data[5] ?? null);
                $member->setCity($data[6] ?? null);
                $member->setCommande($order);
                $member->setClub($club);

                $entityManager->persist($member);
            }

            // Liaison du club à la commande
            $order->setClub($club);

            // Mise à jour du montant total
            $memberCount = (float)$clubData["members"];
            $total = 50.0 + ($memberCount * 5);
            $order->setTotalAmount($total);
        }

        if (isset($cart["club_individuel"])) {
            $member = new Member();
            $member->setFirstName($cart["club_individuel"]["first_name"]);
            $member->setLastName($cart["club_individuel"]["last_name"]);
            $member->setBirthDate($cart["club_individuel"]["birth_date"]);
            $member->setSex($cart["club_individuel"]["sex"]);
            $member->setEmail($cart["club_individuel"]["email"]);
            $member->setCommande($order);

            $entityManager->persist($member);
        }

        if (isset($cart['cultural_registration'])) {
            $member = new Member();
            $member->setFirstName($cart['cultural_registration']['first_name']);
            $member->setLastName($cart['cultural_registration']['last_name']);
            $member->setBirthDate($cart['cultural_registration']['birth_date']);
            $member->setSex($cart['cultural_registration']['sex']);
            $member->setEmail($cart['cultural_registration']['email']);
            $member->setCommande($order); // Lien avec la commande

            $entityManager->persist($member);
        }

        $order->setStatus("payee");

        $entityManager->flush();

        // Nettoyage de la session
        $session->remove("cart");
        $session->remove("orderId");
        $session->remove("csvFile");

        return $this->render("payment/success.html.twig");
    }

    private function getMembersFromCSV(string $filePath): array
    {
        $handle = fopen($filePath, "r");
        fgetcsv($handle); // Ignorer l'en-tête

        $members = [];
        while (($data = fgetcsv($handle, 1000)) !== FALSE) {
            $members[] = $data;
        }

        fclose($handle);
        return $members;
    }

    private function getMembersFromXLSX(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Ignorer la première ligne (en-tête)
        array_shift($rows);

        return $rows;
    }


    #[Route("/paiement-annulation", name: "payment_cancel")]
    public function paymentCancel(
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Récupérer l'ID de la commande depuis la session
        $orderId = $session->get("orderId");

        // Vérification si l'ID de commande est défini
        if (!$orderId) {
            // Logique en cas d'absence de l'ID de commande dans la session
            $this->addFlash('error', 'Aucune commande trouvée pour annulation.');
            return $this->redirectToRoute('app_home');
        }

        // Récupération de la commande depuis la base de données
        $order = $entityManager->getRepository(Order::class)->find($orderId);

        // Vérification si la commande existe
        if (!$order) {
            // Logique en cas d'absence de la commande dans la base de données
            $this->addFlash('error', 'Commande non trouvée dans la base de données.');
            return $this->redirectToRoute('app_home');
        }

        // Mettre à jour le statut de la commande
        $order->setStatus("annulee");
        $entityManager->flush();

        // Nettoyage de la session
        $session->remove('cart');
        $session->remove('orderId');
        $session->remove('csvFile');

        // Afficher la page d'annulation
        return $this->render('payment/cancel.html.twig');
    }
}
