<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdminBundleController extends Controller
{
    /**
     * @Route("/admin/bundle", name="admin_bundle")
     */
    public function index()
    {
        return $this->render('admin_bundle/index.html.twig', [
            'controller_name' => 'AdminBundleController',
        ]);
    }
}
