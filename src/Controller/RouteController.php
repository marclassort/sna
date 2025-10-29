<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\PostRepository;
use App\Service\EmailService;
use DateTime;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class RouteController extends AbstractController
{
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

    #[Route("/blog/{slug}", name: "app_blog_post")]
    public function getPost(PostRepository $postRepository, string $slug): Response
    {
        $post = $postRepository->findOneBy(["slug" => $slug]);

        return $this->render("home/post.html.twig", [
            "post" => $post
        ]);
    }

    #[Route("/evenements", name: "app_evenements")]
    public function getEvenements(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findBy(
            ["status" => "publish"],
            ["eventDate" => "ASC"]
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

    #[Route("/videos", name: "app_videos")]
    public function getVideos(): Response
    {
        return $this->render("home/videos.html.twig");
    }

    #[Route("/aix-ville-imperiale/parcours-imperiaux", name: "app_aix_ville_imperiale")]
    public function getAixVilleImperiale(): Response
    {
        return $this->render("home/aix-ville-imperiale.html.twig");
    }

    #[Route("/adhesion-2025", name: "app_adhesion_2025")]
    public function getAdhesion2025(): Response
    {
        return $this->render("home/adhesion-2025.html.twig");
    }

    #[Route("/adhesion-2026", name: "app_adhesion_2026")]
    public function getAdhesion2026(): Response
    {
        return $this->render("home/adhesion-2026.html.twig");
    }

    #[Route("/visites-guidees", name: "app_visites_guidees")]
    public function getVisitesGuidees(): Response
    {
        return $this->render("home/visites-guidees.html.twig");
    }

    #[Route("/boutique-evenements", name: "app_boutique_evenements")]
    public function getBoutiqueEvenements(): Response
    {
        return $this->render("home/boutique-evenements.html.twig");
    }

    #[Route("/produits", name: "app_produits")]
    public function getProduits(): Response
    {
        return $this->render("home/produits.html.twig");
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

    #[Route('/la-caverne-secrete/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): Response
    {
        return new Response('Déconnecté avec succès.', Response::HTTP_OK, ['WWW-Authenticate' => 'Basic realm="admin"']);
    }
}