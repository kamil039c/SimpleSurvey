<?php
namespace App\Acme\SurveyBundle\Controller;

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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SurveyController extends Controller {
	private $db = null;
	private $session = null;
	private $router;
	
	public function __construct(UrlGeneratorInterface $router) {
		$this->session = new Session();
		$this->router = $router;
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
     * @Route("/", name="home_page")
     */
	 
    public function index(Request $request) {
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
		
		return $this->redirectToRoute('home_page', array(), 301);
	}
	
	/**
    * @Route("/survey_logout")
    */
	
	public function survey_logout(Request $request) {
		$this->session->invalidate();
		return $this->redirectToRoute('home_page', array(), 301);
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
		return $this->redirectToRoute('home_page', array(), 301);
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
		return $this->redirectToRoute('home_page', array(), 301);
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
			
			return $this->redirectToRoute('home_page', array(), 301);
		}
		
		if (empty($request->request->get('text'))) {
			return $this->render('error.html.twig', ['msg' => 'Musisz coś wpisać, aby kontynuować']);
		}
		
		$this->session->set(SurveyTemplates::$questions[$this->session->get('surveyPhase') - 1]['key'], $request->request->get('text'));
		$this->session->set('surveyPhase', $this->session->get('surveyPhase') + 1);
		
		return $this->redirectToRoute('home_page', array(), 301);
	}
}
?>