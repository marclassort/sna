<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Repository\EventRepository;
use App\Repository\PostRepository;
use App\Repository\ProductRepository;
use App\Service\EmailService;
use App\Service\PanierService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
    
    #[Route("/la-sna", name: "app_sna")]
    public function getSNA(): Response
    {
        return $this->render("home/sna.html.twig");
    }

    #[Route("/blog", name: "app_blog")]
    public function getBlog(PostRepository $postRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $queryBuilder = $postRepository->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', 'publish')
            ->orderBy('p.createdAt', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            4
        );

        return $this->render("home/blog.html.twig", [
            "posts" => $pagination
        ]);
    }

    #[Route("/equipe", name: "app_equipe")]
    public function getEquipe(): Response
    {
        return $this->render("home/equipe.html.twig");
    }

    #[Route("/evenements", name: "app_evenements")]
    public function getEvenements(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findBy(
            ["status" => "publish"]
        );

        return $this->render("home/evenements.html.twig", [
            "events" => $events,
        ]);
    }

    #[Route("/galerie", name: "app_galerie")]
    public function getGalerie(): Response
    {
        return $this->render("home/galerie.html.twig");
    }

    #[Route("/adhesion", name: "app_adhesion")]
    public function getAdhesion(ProductRepository $productRepository, SessionInterface $session): Response
    {
        $items = $this->cartService->getCartItems($session);
        $total = $this->cartService->getTotal($session);

        $products = $productRepository->findBy(
            [],
            [
                "id" => "DESC"
            ]);

        return $this->render("home/adhesion.html.twig",
            [
                "products" => $products,
                "items" => $items,
                "total" => $total,
                'stripe_public_key' => $this->getParameter('stripe_public_key')
            ]
        );
    }

    #[Route("/visites-guidees", name: "app_visites_guidees")]
    public function getVisitesGuidees(ProductRepository $productRepository, SessionInterface $session): Response
    {
        $items = $this->cartService->getCartItems($session);
        $total = $this->cartService->getTotal($session);

        $products = $productRepository->findBy(
            [],
            [
                "id" => "DESC"
            ]);

        return $this->render("home/visites-guidees.html.twig",
            [
                "products" => $products,
                "items" => $items,
                "total" => $total,
                'stripe_public_key' => $this->getParameter('stripe_public_key')
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

    #[Route('/add-order', name: 'add_order_to_cart', methods: ['POST', 'GET'])]
    public function addOrderToCart(
        SessionInterface $session,
        EntityManagerInterface $entityManager,
        ProductRepository $productRepository
    ): JsonResponse {
        // Récupérer les informations du panier depuis la session
        $cart = $session->get('cart', []);

        // Calculer le total du panier (vous pouvez adapter ce calcul en fonction de votre logique)
        $totalAmount = 0;

        // Créer la commande
        $order = new Order();
        $order->setOrderType('panier');
        $order->setStatus('en-cours');
        $order->setCreatedAt(new DateTimeImmutable("now"));

        foreach ($cart as $item) {
            $product = $productRepository->find($item['product']->getId());
            $order->addProduct($product);
            $totalAmount += $item['product']->getPrice() * $item['quantity'];
        }

        $order->setTotalAmount($totalAmount);

        // Sauvegarder la commande dans la base de données
        $entityManager->persist($order);
        $entityManager->flush();

        // Enregistrer l'ID de la commande dans la session
        $session->set('orderId', $order->getId());

        // Retourner une réponse JSON pour confirmer la création de la commande
        return new JsonResponse(['success' => true, 'orderId' => $order->getId()]);
    }

    #[Route('/save-order-and-session', name: 'save_order_and_session', methods: ['POST', 'GET'])]
    public function saveOrderAndSession(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $validator = Validation::createValidator();

        $logoFile = $request->files->get('logo');
        $csvFile = $request->files->get('csv');

        // Validation du logo, uniquement des fichiers image
        $logoConstraints = new Assert\File([
            'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif'],
            'mimeTypesMessage' => 'Veuillez télécharger une image valide (jpeg, png ou gif).',
        ]);

        $csvConstraints = new Assert\File([
            'mimeTypes' => [
                'text/csv',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'mimeTypesMessage' => 'Veuillez télécharger un fichier CSV ou Excel valide.',
        ]);

        $logoErrors = $validator->validate($logoFile, $logoConstraints);
        $csvErrors = $validator->validate($csvFile, $csvConstraints);

        if (count($logoErrors) > 0 || count($csvErrors) > 0) {
            return new JsonResponse([
                'success' => false,
                'errors' => [
                    'logo' => (string) $logoErrors,
                    'csv' => (string) $csvErrors,
                ],
            ]);
        }

        $clubName = $request->get('club_name');
        $logoFile = $request->files->get('logo');
        $csvFile = $request->files->get('csv');
        $email = $request->get('email');
        $presidentName = $request->get('president_name');
        $treasurerName = $request->get('treasurer_name');
        $address = $request->get('address');
        $address2 = $request->get('address2');
        $zip = $request->get('zip');
        $city = $request->get('city');
        $country = $request->get('country');
        $memberCount = $request->get('member_count');

        // Enregistrement du logo si présent
        if ($logoFile) {
            $logoFilename = uniqid() . '.' . $logoFile->guessExtension();
            $logoFile->move($this->getParameter('upload_directory'), $logoFilename);
        } else {
            $logoFilename = null;
        }

        // Enregistrement du fichier CSV/Excel
        if ($csvFile) {
            $csvFilename = uniqid() . '.' . $csvFile->guessExtension();
            $csvFile->move($this->getParameter('upload_directory'), $csvFilename);
            $csvFilePath = $this->getParameter('upload_directory') . '/' . $csvFilename;
        } else {
            return new JsonResponse(['error' => 'No CSV or Excel file uploaded'], 400);
        }

        $memberCount = (int) $request->get('member_count');

        $cart['club'] = [
            'club_name' => $clubName,
            'logo' => $logoFilename,
            'email' => $email,
            'president_name' => $presidentName,
            'treasurer_name' => $treasurerName,
            'address' => $address,
            'address2' => $address2,
            'zip' => $zip,
            'city' => $city,
            'country' => $country,
            'members' => $memberCount,
            'csvFilePath' => $csvFilePath
        ];

        $session->set('cart', $cart);

        $order = new Order();
        $order->setOrderType('inscription-club');
        $order->setTotalAmount(50 + ($memberCount * 5));
        $order->setStatus('en-cours');
        $order->setCreatedAt(new DateTimeImmutable());

        $entityManager->persist($order);
        $entityManager->flush();

        $session->set('orderId', $order->getId());

        return new JsonResponse(['success' => true]);
    }

    #[Route('/add-individual-registration', name: 'add_individual_registration_to_cart', methods: ['POST'])]
    public function addIndividualRegistrationToCart(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $price = $data['price'];
        $registrationData = $data['registrationData'];

        $cart = $session->get('cart', []);

        $firstName = $registrationData['firstName'];
        $lastName = $registrationData['lastName'];
        $birthDate = $registrationData['birthDate'];
        $sex = $registrationData['sex'];
        $email = $registrationData['email'];

        $cart['club_individuel'] = [
            'price' => $price,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $birthDate,
            'sex' => $sex,
            'email' => $email,
        ];

        $session->set('cart', $cart);

        $order = new Order();
        $order->setOrderType('inscription-individuelle');
        $order->setTotalAmount(10);
        $order->setStatus('en-cours');
        $order->setCreatedAt(new DateTimeImmutable("now"));

        $entityManager->persist($order);
        $entityManager->flush();

        $session->set('orderId', $order->getId());

        return new JsonResponse(['success' => true]);
    }

    #[Route('/add-cultural-registration', name: 'add_cultural_registration_to_cart', methods: ['POST'])]
    public function addCulturalRegistrationToCart(
        Request $request,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $price = $data['price'];
        $registrationData = $data['registrationData'];

        $cart = $session->get('cart', []);

        $firstName = $registrationData['firstName'];
        $lastName = $registrationData['lastName'];
        $birthDate = $registrationData['birthDate'];
        $sex = $registrationData['sex'];
        $email = $registrationData['email'];

        $cart['cultural_registration'] = [
            'price' => $price,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => $birthDate,
            'sex' => $sex,
            'email' => $email,
        ];

        $session->set('cart', $cart);

        $order = new Order();
        $order->setOrderType('inscription-arts-culturels');
        $order->setTotalAmount(10);
        $order->setStatus('en-cours');
        $order->setCreatedAt(new DateTimeImmutable("now"));

        $entityManager->persist($order);
        $entityManager->flush();

        $session->set('orderId', $order->getId());

        return new JsonResponse(['success' => true]);
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

    #[Route("/filter/products", name: "filter_products_by_price", methods: ["GET"])]
    public function filterProductsByPrice(Request $request, ProductRepository $productRepository): JsonResponse
    {
        $priceRange = $request->query->get('priceRange');

        // Séparer la plage de prix pour obtenir le min et le max
        [$minPrice, $maxPrice] = explode('-', $priceRange);

        // Récupérer les produits correspondant à la plage de prix
        $products = $productRepository->findByPriceRange((float) $minPrice, (float) $maxPrice);

        // Retourner une réponse JSON
        $responseProducts = [];

        foreach ($products as $product) {
            $responseProducts[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'image' => $product->getImage(),
                'category' => $product->getCategory(),
            ];
        }

        return new JsonResponse(['products' => $responseProducts]);
    }

    /**
     * @throws SyntaxError
     * @throws TransportExceptionInterface
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, EmailService $emailService): Response
    {
        if ($request->isXmlHttpRequest()) {
            $data = json_decode($request->getContent(), true);

            $emailService->sendContactEmail(
                'societenapoleonienne.aix@gmail.com',
                'Site web SNA - Prise de contact',
                $data
            );

            return $this->json(['success' => true]);
        }

        return $this->render('home/contact.html.twig');
    }

    #[Route("/politique-de-confidentialite", name: "app_politique_de_confidentialite")]
    public function getPrivacyPolicy(): Response
    {
        return $this->render("home/politique-de-confidentialite.html.twig");
    }

    #[Route('//la-caverne-secrete/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): Response
    {
        return new Response('Déconnecté avec succès.', Response::HTTP_OK, ['WWW-Authenticate' => 'Basic realm="admin"']);
    }
}