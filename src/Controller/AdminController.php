<?php

namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends AbstractController
{
    /**
     * @Route("/la-caverne-secrete", name="admin_home")
     */
    public function index(): Response
    {
        return $this->render('admin/my-dashboard.html.twig');
    }
}