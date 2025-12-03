<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class FrontcontrollerController extends AbstractController
{
    #[Route('/frontoffice', name: 'app_frontcontroller')]
    public function index(): Response
    {
        return $this->render('frontcontroller/frontoffice.html.twig', [
            'controller_name' => 'FrontcontrollerController',
        ]);
    }
}
