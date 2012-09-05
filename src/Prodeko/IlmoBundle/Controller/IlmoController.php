<?php 
namespace Prodeko\IlmoBundle\Controller;


use Prodeko\IlmoBundle\Entity\MultipleChoiceEntry;

use Prodeko\IlmoBundle\Entity\FreeTextEntry;

use Prodeko\IlmoBundle\Form\Type\RegistrationType;

use Prodeko\IlmoBundle\Entity\Event;

use Prodeko\IlmoBundle\Entity\Registration;

use Prodeko\IlmoBundle\Helpers\Helpers;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\HttpFoundation\Response;


class IlmoController extends Controller
{
	//listaa kaikki tapahtumat
	public function listAction()
	{
		$repository = $this->getDoctrine()->getRepository('ProdekoIlmoBundle:Event');
		$now = new \DateTime();
		
		//Listaa tapahtumat, joiden ilmo on tulevaisuudessa
		$query = $repository->createQueryBuilder('e')
   			->where('e.registrationStarts > :now')
	    	->setParameter('now', $now)
	    	->orderBy('e.registrationStarts', 'ASC')
	    	->getQuery();

		$upcomingEvents = $query->getResult();
		
		//Listaa tapahtumat, joiden ilmo on käynnissä
		$query = $repository->createQueryBuilder('e')
			->where('e.registrationStarts < :now')
			->andWhere('e.registrationEnds > :now')
			->setParameter('now', $now)
			->orderBy('e.takesPlace', 'ASC')
			->getQuery();
		$activeEvents = $query->getResult();
		
		
		//Listaa tapahtumia, joiden ilmo on jo sulkeutunut
		$query = $repository->createQueryBuilder('e')
			->where('e.registrationEnds < :now')
			->andWhere('e.takesPlace > :now')
			->setParameter('now', $now)
			->getQuery();
		$pastEvents = $query->getResult();
		
		return $this->render('ProdekoIlmoBundle:Ilmo:eventlist.html.twig',
				array('activeEvents' => $activeEvents,
					  'upcomingEvents' => $upcomingEvents,
					  'pastEvents' => $pastEvents)
				);
	}
	
	//Näyttää yhden tapahtuman tiedot
	public function showAction($id, Request $request)
	{
		$kiltis = false;
		if($ip = $request->getClientIp() === $this->container->getParameter('kiltis_ip')) {
			$kiltis = true;
		}
		//Hae tapahtuma URI:sta tulleen id:n perusteella
		$event = $this->getDoctrine()
			->getRepository('ProdekoIlmoBundle:Event')
			->findOneBy(array('id' => $id));
		//Hae jonossa olevat ilmot
		$queue = Helpers::getQueue($event, 
				$this->getDoctrine()->getRepository('ProdekoIlmoBundle:Registration'));

		//Luo uusi ilmoittautumisolio ja liitä sille kyseinen tapahtuma
		$registration = Helpers::createRegistrationObject($event);
		//Tee ilmoittautumislomake, määrittely löytyy Prodeko\IlmoBundle\Form\Type\RegistrationType
		$form = $this->createForm(new RegistrationType($event), $registration);
		
		//Anna templatelle muuttujat 
		$variables = array(
				'event' => $event,
				'queue' => $queue,
				'form' => $form->createView(),
				'kiltis'	=> $kiltis,
				);
		return $this->render('ProdekoIlmoBundle:Ilmo:event.html.twig', $variables);
	}
	
	/*
	 * Tässä controllerissa käsitellään ilmoittautumisrequest,
	 * joka lähetetään ilmoittautumisnapilla.
	 */
	public function registerAction(Request $request, $id)
	{
		//Jos sivu on haettu POSTilla, on kyseessä ilmoittautumisen käsittely
		if ($request->getMethod() == 'POST') {
			$event = $this->getDoctrine()
							->getRepository('ProdekoIlmoBundle:Event')
							->findOneBy(array('id' => $id));
			//Luo uusi ilmoittautumisolio ja liitä sille kyseinen tapahtuma
			$registration = Helpers::createRegistrationObject($event);
			$form = $this->createForm(new RegistrationType($event), $registration);
			$form->bindRequest($request);
			
			/* Tarkasta lomake, isValid näyttää automaattisesti errorit, jos niitä on. 
			 * TODO: tarkista, mistä request on tullut. Jos kyseessä on jonotus,
			 * ohjaa sendConfirmationEmailin asemesta jononäkymään.
			 */
			if ($form->isValid()) {
				//Lisää lomakkeelta tulleet tiedot registration-olioon
				$registration = $form->getData();
				$time = new \DateTime();
				$registration->setRegistrationTime($time);
				$token = Helpers::getRegistrationToken($registration);
				$registration->setToken($token);
				
				//Tallenna ilmoittautuminen tietokantaan
				$em = $this->getDoctrine()->getEntityManager();
				$em->persist($registration);
				$em->flush();
				
				//Lähetä vahvistusviesti
				return $this->forward('ProdekoIlmoBundle:Ilmo:sendConfirmationEmail',
						array(	'event' => $event,
								'token' => $token,
								'email' => $registration->getEmail()));
			}
			else {
				return new Response(var_dump($form->getErrors()));
			}
		}
		/*Jos register-routella on tehty GET-kutsu, ohjataan
		tapahtumasivulle */
		else {
			return $this->redirect($this->generateUrl('show',
					array('id' => $id)));
		}
	}
	
	/*Lähettää ilmoittautuneelle sähköpostitse vahvistusviestin,
	 joka sisältää linkin ilmon poistamiseen */
	public function sendConfirmationEmailAction($email, $token, Event $event, Request $request)
	{
		//Siirretty alkuun ohittaakseen
		return $this->redirect($this->generateUrl('show', array('id' => $event->getId() )));
		$messageBody = "Ilmoittautumisesi tapahtumaan " . $event->getName() . " on tallennettu.\n" .
						"Voit poistaa ilmoittautumisesi allaolevasta linkistä.\n" . 
						"http://ilmogilge.no-ip.org/app.php/fi/remove/" . $token;
		$message = \Swift_Message::newInstance()
		->setSubject($event->getName() . ' / Ilmoittautuminen tallennettu')
		->setFrom('ilmogilge@gmail.com')
		->setTo($email)
		->setBody($messageBody);
		$this->get('mailer')->send($message);
		//TODO: näytä jonkinlainen viesti "ilmoittautuminen onnistui"
		
	}
	
	/*Luo jononäkymän, jossa tapahtumiin jonotetaan (kiltisjono) */
	public function queueAction($id, Request $request)
	{
		$event = $this->getDoctrine()->getRepository('ProdekoIlmoBundle:Event')
			     	  ->findOneBy(array('id' => $id));
		
		//Jos ilmo on alkanut, ohjataan tapahtumasivulle
		if($event->isOpen() || $event->registrationEnded()) {
			return $this->redirect($this->generateUrl('show', array('id' => $id)));
		}
		$registration = Helpers::createRegistrationObject($event);
		$form = $this->createForm(new RegistrationType($event), $registration);
		
		$variables = array(
				'event' => $event,
				'form' => $form->createView());
		return $this->render('ProdekoIlmoBundle:Ilmo:queue.html.twig', $variables);
	}
	
	public function removePromptAction($token, Request $request)
	{
		return $this->render('ProdekoIlmoBundle:Ilmo:removeprompt.html.twig', 
				array('token' => $token));
	}
	
	
	public function removeRegistrationAction($token, Request $request) 
	{
		//Poistaa ilmoittautumisen annetulla id:llä. Tähän pitää tehdä varmennussysteemit.
		$em = $this->getDoctrine()->getEntityManager();
		$registration = $this->getDoctrine()
			->getRepository('ProdekoIlmoBundle:Registration')
			->findOneBy(array('token' => $token));
		if (!$registration) {
			//Heitä etusivulle, jos ilmoittautumista ei löydy annetulla id:llä.
			return $this->redirect($this->generateUrl("list"));
		}
		$event = $registration->getEvent();
		
		// Poista ilmoittautumisen vapaateksti- ja monivalintaentryt
		foreach ($registration->getFreeTextEntries() as $freeTextEntry) $em->remove($freeTextEntry);
		foreach ($registration->getMultipleChoiceEntries() as $multipleChoiceEntry) $em->remove($multipleChoiceEntry);
		
		// Poista itse ilmoittautuminen ja tallenna.
		$em->remove($registration);
		$em->flush();
		//Ohjaa tarkastelemaan tapahtumaa
		//TODO: Ohjaa takaisin adminin ilmoittautumiset - näkymään, jos pyyntö tullut sieltä.
		return $this->redirect($this->generateUrl("show", array('id' => $event->getId())));
	}
	
	public function registrationSuccessAction($token, Request $request)
	{
		return $this->render('ProdekoIlmoBundle:Ilmo:registrationSuccess.html.twig', array('token' => $token));
	}

}
?>
