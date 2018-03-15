<?php
namespace App\Controller;

require("../src/Utils/PasswordUtil.php");
require("../src/Utils/SurveyTemplates.php");

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Driver\Connection;
use \App\Utils\PasswordUtil;
use \App\Utils\SurveyTemplates;
use PDO;

use App\Entity\Ankieta;
use App\Entity\User;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class SurveyBundleController extends Controller {
	private $db = null;
	
	private function checkDB() {
		if ($this->db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'")->fetch(PDO::FETCH_NUM)[0] > 0) {
			return true;
		}
		
		try {
			$this->db->beginTransaction();
			
			$this->db->exec("CREATE TABLE `ankiety` (
				`id` INTEGER AUTO_INCREMENT,
				`uid` INTEGER,
				`imie` varchar(50),
				`nazwisko` varchar(50),
				`wiek` INTEGER
			)");
		
			$this->db->exec("CREATE TABLE `users` (
				`id` INTEGER AUTO_INCREMENT,
				`name` varchar(50),
				`pwd` varchar(60),
				`session_token` varchar(8) DEFAULT 'abcd7654'
			)");
			
			$imiona = ["mariusz","mateusz","franio","bogusław","mietek","wiesio","łukasz","sebastian"];
			$nazwiska = ["kowalski","nowak","cieplak","marczuk","kowalik","niegłowski","ćwierkacz"];
		
			for ($i = 0; $i < 50; $i++) {
				$this->db->query("INSERT INTO ankiety(uid,imie,nazwisko,wiek) VALUES("
					.mt_rand(1,7).",'".$imiona[mt_rand(0, count($imiona) - 1)]."','".$nazwiska[mt_rand(0, count($nazwiska) - 1)]."',".mt_rand(17,66).")");
			}
			
			$this->db->exec("INSERT INTO `users` (`id`, `name`, `pwd`, `session_token`) VALUES
				(1, 'kamil', '" . PasswordUtil::hashPw('123456') . "', 'abcd7654'),
				(2, 'franek', '" . PasswordUtil::hashPw('123456') . "', 'abcd7654'),
				(3, 'marian', '" . PasswordUtil::hashPw('123456') . "', 'abcd7654'),
				(4, 'malgoska', '" . PasswordUtil::hashPw('123456') . "', 'abcd7654'),
				(5, 'kaska', '" . PasswordUtil::hashPw('123456') . "', 'abcd7654'),
				(6, 'mateusz', '" . PasswordUtil::hashPw('123456') . "', 'abcd7654'),
				(7, 'adam', '" . PasswordUtil::hashPw('123456') . "', 'abcd7654')");
			
			$this->db->commit();
		} catch(PDOException $e) {
			die($e->getMessage());
		}
		
		return true;
	}
	
	private function getSurveyUser() {
		if (empty($_SESSION['uid']) || empty($_SESSION['token'])) return null;
		
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT u FROM App\Entity\User u WHERE u.id = ?1 AND u.session_token = ?2");
		$query->setParameter(1, $_SESSION['uid']);
		$query->setParameter(2, $_SESSION['token']);
		$user = $query->getResult();
		
		if (count($user) == 0) {
			session_destroy();
			return null;
		}
		
		return $user[0]->getRow();
	}
	
    /**
     * @Route("/index", name="index")
     */
	 
    public function index(Request $request, Connection $db) {
		if (!isset($db)) {
			return $this->render('error.html.twig', [
				'msg' => 'Nie można nawiązać połączenia z bazą danych'
			]);
		}
		
		$this->db = $db;
		$this->checkDB();
		
		$ankietaFaza = 0;
		$ankieta = null;
		$ankiety = [];
		
		if (($zalogowanyUser = $this->getSurveyUser()) != null) {
			$ankietaFaza = (int)$_SESSION['ankietaFaza'];
			if (isset(SurveyTemplates::$questions[$ankietaFaza - 1])) $ankieta = SurveyTemplates::$questions[$ankietaFaza - 1];
			
			$em = $this->getDoctrine()->getManager();
			$query = $em->createQuery("SELECT u FROM App\Entity\Ankieta u WHERE u.uid = ?1");
			$query->setParameter(1, $zalogowanyUser['id']);
			//print_r($query->getResult());
			//exit;
			foreach ($query->getResult() as $a) {
				if (isset($a)) $ankiety[] = $a->getRow();
			}
		}
		
		$user = new User();

        $form = $this->createFormBuilder($user)
			->setAction($this->generateUrl('r_login'))
            ->setMethod('POST')
            ->add('name', TextType::class, array('label' => 'Login:'))
            ->add('pwd', PasswordType::class, array('label' => 'Hasło:'))
            ->add('login', SubmitType::class, array('label' => '[zaloguj się]'))
            ->getForm();
		
        return $this->render(
			'survey_bundle/index.html.twig', 
			[
				'form' => $form->createView(), 'user' => $zalogowanyUser, 'ankietaFaza' => $ankietaFaza, 'ankieta' => $ankieta, 
				'iloscPytan' => count(SurveyTemplates::$questions), 'ankiety' => $ankiety
			]
		);
    }
	
	/**
    * @Route("/login", methods="POST", name="r_login")
    */
	
	public function login(Request $request) {
		$form = $request->request->get('form');
		if (empty($form['name']) || empty($form['pwd'])) {
			return $this->render('error.html.twig', ['msg' => 'Wprowadź login i hasło']);
		}
		
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT u FROM App\Entity\User u WHERE u.name = ?1");
		$query->setParameter(1, $form['name']);
		$user = $query->getResult();
		
		if (count($user) == 0) {
			return $this->render(
				'error.html.twig', 
				['msg' => 'User o nazwie "' . $form['name'] . '" nie istnieje!']
			);
		}
		
		$row = $user[0]->getRow();
		
		if (!PasswordUtil::checkPw($form['pwd'], $row['pwd'])) {
			return $this->render('error.html.twig', ['msg' => 'Autentykacja nie powiodła się!']);
		}
		
		session_start();
		
		$_SESSION['uid'] = $row['id'];
		$_SESSION['token'] = $row['session_token'];
		$_SESSION['ankietaFaza'] = 0;
		
		return $this->redirectToRoute('index', array(), 301);
	}
	
	/**
    * @Route("/survey_logout")
    */
	
	public function survey_logout(Request $request) {
		session_destroy();
		return $this->redirectToRoute('index', array(), 301);
	}
	
	/**
    * @Route("/startSurvey")
    */
	
	public function startSurvey(Request $request) {
		if ($this->getSurveyUser() === null) {
			return $this->render('error.html.twig', ['msg' => 'Brak autoryzacji!']);
		}
		
		if ($_SESSION['ankietaFaza'] != 0) {
			return $this->render('error.html.twig', ['msg' => 'Ankieta została rozpoczęta!']);
		}
		
		$_SESSION['ankietaFaza'] = 1;
		return $this->redirectToRoute('index', array(), 301);
	}
	
	/**
    * @Route("/abortSurvey")
    */
	
	public function abortSurvey(Request $request) {
		if ($this->getSurveyUser() === null) {
			return $this->render('error.html.twig', ['msg' => 'Brak autoryzacji!']);
		}
		
		if ($_SESSION['ankietaFaza'] == 0) {
			return $this->render('error.html.twig', ['msg' => 'Ankieta nie została rozpoczęta!']);
		}
		
		$_SESSION['ankietaFaza'] = 0;
		foreach(SurveyTemplates::$questions as $question) $_SESSION[$question['klucz']] = "";
		return $this->redirectToRoute('index', array(), 301);
	}
	
	/**
    * @Route("/nextStepSurvey", methods="POST")
    */
	
	public function nextStepSurvey(Request $request) {
		if (($user = $this->getSurveyUser()) === null) {
			return $this->render('error.html.twig', ['msg' => 'Brak autoryzacji!']);
		}
		
		if ($_SESSION['ankietaFaza'] == 0) {
			return $this->render('error.html.twig', ['msg' => 'Ankieta nie została rozpoczęta!']);
		}
		
		if ($_SESSION['ankietaFaza'] == 4) {
			$_SESSION['ankietaFaza'] = 0;
			
			$ankieta = new Ankieta();
			$ankieta->set('uid', $user['id']);
			
			foreach(SurveyTemplates::$questions as $pytanie) {
				$ankieta->set($pytanie['field'], $pytanie['isint'] ? (int)$_SESSION[$pytanie['klucz']] : (string)$_SESSION[$pytanie['klucz']]);
				$_SESSION[$pytanie['klucz']] = "";
			}
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($ankieta);
			$em->flush();
			
			return $this->redirectToRoute('index', array(), 301);
		}
		
		if (empty($request->request->get('text'))) {
			return $this->render('error.html.twig', ['msg' => 'Musisz coś wpisać, aby kontynuować']);
		}
		
		$_SESSION[SurveyTemplates::$questions[$_SESSION['ankietaFaza'] - 1]['klucz']] = $request->request->get('text');
		$_SESSION['ankietaFaza']++;
		
		return $this->redirectToRoute('index', array(), 301);
	}
}
?>