<?php

namespace App\Controller;

use App\Entity\Product;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function index(): Response
    {
        $user = $this->getUser();
        $productsRepository = $this->getDoctrine()->getRepository(Product::class);
        $products = $productsRepository->findBy(['seller' => $user]);
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'products' => $products
        ]);
    }

    /**
     * @Route("/browse", name="browse")
     */
    public function browse(): Response
    {
        $productsRepository = $this->getDoctrine()->getRepository(Product::class);
        $products = $productsRepository->findBy(['available' => true]);
        return $this->render('dashboard/browse.html.twig', [
            'controller_name' => 'DashboardController',
            'products' => $products
        ]);
    }
}
