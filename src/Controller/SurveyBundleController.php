<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SurveyBundleController extends Controller
{
    /**
     * @Route("/survey/bundle", name="survey_bundle")
     */
    public function index()
    {
        return $this->render('survey_bundle/index.html.twig', [
            'controller_name' => 'SurveyBundleController',
        ]);
    }
}
