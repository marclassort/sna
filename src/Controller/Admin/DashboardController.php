<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Member;
use App\Entity\Order;
use App\Entity\Post;
use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    #[Route('/la-caverne-secrete', name: 'admin')]
    public function index(): Response
    {
        return $this->render('admin/my-dashboard.html.twig');    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('SNA');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::section("Adhésions", "fa fa-address-card");
        yield MenuItem::linkToCrud('Membres', 'fa fa-user', Member::class);
        yield MenuItem::section("Blog", "fa fa-pencil");
        yield MenuItem::linkToCrud('Articles', 'fa fa-pencil', Post::class);
        yield MenuItem::linkToCrud('Catégories', 'fa fa-pencil', Category::class);
        yield MenuItem::section("Événements", "fa fa-calendar");
        yield MenuItem::linkToCrud('Événements', 'fa fa-calendar', Event::class);
        yield MenuItem::section("Boutique", "fa fa-shopping-cart");
        yield MenuItem::linkToCrud('Produits', 'fa fa-shopping-cart', Product::class);
        yield MenuItem::linkToCrud('Commandes', 'fa fa-shopping-cart', Order::class);
    }
}
