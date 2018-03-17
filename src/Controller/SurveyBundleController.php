<?php
namespace App\Controller;

use Doctrine\DBAL\Driver\Connection;

use App\Utils\PasswordUtil;
use App\Utils\SurveyTemplates;

use App\Entity\Survey;
use App\Entity\User;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

use PDO;

class SurveyBundleController extends Controller {
	private $db = null;
	private $session = null;
	
	public function __construct() {
		$this->session = new Session();
	}
	
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
		if (empty($this->session->get('uid')) || empty($this->session->get('token'))) return null;
		
		$em = $this->getDoctrine()->getManager();
		$query = $em->createQuery("SELECT u FROM App\Entity\User u WHERE u.id = ?1 AND u.session_token = ?2");
		$query->setParameter(1, $this->session->get('uid'));
		$query->setParameter(2, $this->session->get('token'));
		$user = $query->getResult();
		
		if (count($user) == 0) {
			session_destroy();
			$this->session->invalidate();
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
		
		$surveyPhase = 0;
		$survey = null;
		$userSurveys = [];
		
		if (($loggedInUser = $this->getSurveyUser()) != null) {
			$surveyPhase = (int)$this->session->get('surveyPhase');
			if (isset(SurveyTemplates::$questions[$surveyPhase - 1])) $survey = SurveyTemplates::$questions[$surveyPhase - 1];
			
			$em = $this->getDoctrine()->getManager();
			$query = $em->createQuery("SELECT u FROM App\Entity\Survey u WHERE u.uid = ?1");
			$query->setParameter(1, $loggedInUser['id']);
			
			foreach ($query->getResult() as $a) {
				if (isset($a)) $userSurveys[] = $a->getRow();
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
				'loginForm' => $form->createView(), 'user' => $loggedInUser, 'surveyPhase' => $surveyPhase, 'survey' => $survey, 
				'qestionsCount' => count(SurveyTemplates::$questions), 'userSurveys' => $userSurveys
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
		
		//$this->session->start();
		$this->session->set('uid', $row['id']);
		$this->session->set('token', $row['session_token']);
		$this->session->set('surveyPhase', 0);
		
		return $this->redirectToRoute('index', array(), 301);
	}
	
	/**
    * @Route("/survey_logout")
    */
	
	public function survey_logout(Request $request) {
		$this->session->invalidate();
		return $this->redirectToRoute('index', array(), 301);
	}
	
	/**
    * @Route("/startSurvey")
    */
	
	public function startSurvey(Request $request) {
		if ($this->getSurveyUser() === null) {
			return $this->render('error.html.twig', ['msg' => 'Brak autoryzacji!']);
		}
		
		if ($this->session->get('surveyPhase') != 0) {
			return $this->render('error.html.twig', ['msg' => 'Ankieta została rozpoczęta!']);
		}
		
		$this->session->set('surveyPhase', 1);
		return $this->redirectToRoute('index', array(), 301);
	}
	
	/**
    * @Route("/abortSurvey")
    */
	
	public function abortSurvey(Request $request) {
		if ($this->getSurveyUser() === null) {
			return $this->render('error.html.twig', ['msg' => 'Brak autoryzacji!']);
		}
		
		if ($this->session->get('surveyPhase') == 0) {
			return $this->render('error.html.twig', ['msg' => 'Ankieta nie została rozpoczęta!']);
		}
		
		$this->session->set('surveyPhase', 0);
		foreach(SurveyTemplates::$questions as $question) $this->session->set($question['key'], "");
		return $this->redirectToRoute('index', array(), 301);
	}
	
	/**
    * @Route("/nextStepSurvey", methods="POST")
    */
	
	public function nextStepSurvey(Request $request) {
		if (($user = $this->getSurveyUser()) === null) {
			return $this->render('error.html.twig', ['msg' => 'Brak autoryzacji!']);
		}
		
		if ($this->session->get('surveyPhase') == 0) {
			return $this->render('error.html.twig', ['msg' => 'Ankieta nie została rozpoczęta!']);
		}
		
		if ($this->session->get('surveyPhase') == 4) {
			$this->session->set('surveyPhase', 0);
			
			$survey = new Survey();
			$survey->set('uid', $user['id']);
			
			foreach(SurveyTemplates::$questions as $question) {
				$survey->set($question['field'], $question['isint'] ? (int)$this->session->get($question['key']) : (string)$this->session->get($question['key']));
				$this->session->set($question['key'], "");
			}
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($survey);
			$em->flush();
			
			return $this->redirectToRoute('index', array(), 301);
		}
		
		if (empty($request->request->get('text'))) {
			return $this->render('error.html.twig', ['msg' => 'Musisz coś wpisać, aby kontynuować']);
		}
		
		$this->session->set(SurveyTemplates::$questions[$this->session->get('surveyPhase') - 1]['key'], $request->request->get('text'));
		$this->session->set('surveyPhase', $this->session->get('surveyPhase') + 1);
		
		return $this->redirectToRoute('index', array(), 301);
	}
}
?>