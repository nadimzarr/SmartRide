<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdmincontrollerController extends AbstractController
{
    #[Route('/backoffice', name: 'app_admincontroller')]
    public function index(): Response
    {
        return $this->render('admincontroller/admindashboard.html.twig', [
            'controller_name' => 'AdmincontrollerController',
        ]);
    }
}
