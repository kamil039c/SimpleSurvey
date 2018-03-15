<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Ankieta;
use App\Entity\User;

class AdminBundleController extends Controller
{
    /**
     * @Route("/admin/bundle", name="admin_bundle")
     */
    public function index(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$qb = $em->createQueryBuilder();
		
		$qb->select('count(u.id)');
		$qb->from('App\Entity\Ankieta','u');
		$ankietyIlosc = $qb->getQuery()->getSingleScalarResult();
		
		$ankietyNaStrone = 10;
		$strony = ceil($ankietyIlosc / $ankietyNaStrone) - 1;
		if ($strony < 0) $strony = 0;
		
		$strona = (int)$request->query->get('page');
		if ($strona < 0) $strona = 0;
		if ($strona > $strony) $strona = $strony;
		
		$sortBY = (string)$request->query->get('sortby');
		if (empty($sortBY)) $sortBY = 'id';
		
		$qb = $em->createQueryBuilder();
		$qb->select('u');
		$qb->setFirstResult( $ankietyNaStrone * $strona );
		$qb->add('orderBy', 'u.' . $sortBY . ' ASC');
		$qb->setMaxResults( $ankietyNaStrone );
		
		$qb->from('App\Entity\Ankieta','u');
		
		$ankiety = [];
		foreach ($qb->getQuery()->getResult() as $ankieta) {
			if (isset($ankieta)) $ankiety[] = $ankieta->getRow();
		}
		
		return $this->render('admin_bundle/index.html.twig', ['ankiety' => $ankiety, 'iloscAnkiet' => $ankietyIlosc ,'sortby' => $sortBY, 'strony' => $strony, 'strona' => $strona]);
    }
}
