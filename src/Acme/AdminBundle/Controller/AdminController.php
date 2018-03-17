<?php
namespace App\Acme\AdminBundle\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Survey;
use App\Entity\User;

class AdminController extends Controller {
	/**
     * @Route("/index", name="index")
     */
	 
    public function index(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$qb = $em->createQueryBuilder();
		
		$qb->select('count(u.id)');
		$qb->from('App\Entity\Survey','u');
		$totalSurveysCount = $qb->getQuery()->getSingleScalarResult();
		
		$surveysPerPage = 10;
		$pages = ceil($totalSurveysCount / $surveysPerPage) - 1;
		if ($pages < 0) $pages = 0;
		
		$page = (int)$request->query->get('page');
		if ($page < 0) $page = 0;
		if ($page > $pages) $page = $pages;
		
		$sortBY = (string)$request->query->get('sortby');
		if (empty($sortBY)) $sortBY = 'id';
		
		$qb = $em->createQueryBuilder();
		$qb->select('u');
		$qb->setFirstResult( $surveysPerPage * $page );
		$qb->add('orderBy', 'u.' . $sortBY . ' ASC');
		$qb->setMaxResults( $surveysPerPage );
		
		$qb->from('App\Entity\Survey','u');
		
		$surveys = [];
		foreach ($qb->getQuery()->getResult() as $survey) {
			if (isset($survey)) $surveys[] = $survey->getRow();
		}
		
		return $this->render('admin_bundle/index.html.twig', [
			'surveys' => $surveys, 'totalSurveysCount' => $totalSurveysCount,
			'sortby' => $sortBY, 'pages' => $pages, 'page' => $page
		]);
    }
}
?>