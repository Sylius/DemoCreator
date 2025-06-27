<?php

declare(strict_types=1);

namespace App\StoreDesigner\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AppController extends AbstractController
{
    #[Route('/{appPath}', name: 'app', requirements: ['appPath' => '.*'], defaults: ['appPath' => null])]
    public function app(): Response
    {
        return $this->render('app.html.twig');
    }
}
