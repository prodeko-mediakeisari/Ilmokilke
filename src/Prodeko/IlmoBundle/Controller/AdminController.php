<?php 
namespace Prodeko\IlmoBundle\Controller;


use Symfony\Component\Security\Core\SecurityContext;

use Prodeko\IlmoBundle\Entity\MultipleChoiceField;

use Prodeko\IlmoBundle\Entity\FreeTextEntry;

use Prodeko\IlmoBundle\Entity\Quota;

use Prodeko\IlmoBundle\Form\Type\RegistrationType;
use Prodeko\IlmoBundle\Form\Type\EventType;

use Prodeko\IlmoBundle\Entity\Event;

use Prodeko\IlmoBundle\Entity\Registration;

use Symfony\Component\HttpFoundation\Request;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Prodeko\IlmoBundle\Controller\IlmoController;

use Symfony\Component\HttpFoundation\Response;

use Prodeko\IlmoBundle\Helpers\Helpers;

class AdminController extends IlmoController
{
	const STATE_NEW_EVENT = 1;
	const STATE_OLD_EVENT_NO_REGISTRANTS = 2;
	const STATE_OLD_EVENT_REGISTRANTS = 3;
	
	/*
	 * Luo lomakkeen, jota käytetään uuden tapahtuman luomisessa
	 * sekä vanhan muokkaamisessa
	 */
	public function createEventFormAction($id, Request $request) 
	{
		if ($id != 0) {
			$event = $this->getDoctrine()
			->getRepository('ProdekoIlmoBundle:Event')
			->findOneBy(array('id' => $id));
			if ($event) {
				$registrations = $event->getRegistrations();
				if ($registrations && count($registrations) > 0) {
					$state = self::STATE_OLD_EVENT_REGISTRANTS; // Sellaisen tapahtuman muokkaus, jolla on ilmoittautumisia.
				} else {
					$state = self::STATE_OLD_EVENT_NO_REGISTRANTS; // Muokkaus, ei ilmoittautumisia.
				}
			} else {
				throw $this->createNotFoundException('Tapahtumaa ei löydy');
				//echo "Ei löydy tapahtumaa!"; die; //TODO: Tähän ehkä joku exception handling
			}
		}
		else {
			$state = self::STATE_NEW_EVENT; // Uuden tapahtuman luonti.
			$event = new Event();
			/*
			$quota_names = array('I','II','III','IV','N');
			
			for($i=1;$i<=5;$i++) {
				$quota = new Quota();
				$quota->setYearOfStudiesValue($i);
				$quota->setName($quota_names[$i-1]);
				$quota->setEvent($event);
				$event->addQuota($quota);
			
			}*/
			$now = new \DateTime('today midnight');
			$event->setTakesPlace($now);
			$event->setRegistrationStarts($now);
			$event->setRegistrationEnds($now);
			$event->setKiltisilmo(true);
			$event->setSizeOfOpenQuota(0);
				
		}
		
		//Tee ilmoittautumislomake, määrittely löytyy Prodeko\IlmoBundle\Form\Type\EventType
		$form = $this->createForm(new EventType(), $event);
		
		//Jos kyseessä on lomakkeen käsittely, eikä lomakkeen näyttö
		if ($request->getMethod() == 'POST') {
			
			$em = $this->getDoctrine()->getEntityManager();
			
			if ($state == self::STATE_OLD_EVENT_REGISTRANTS) { // Eli jos muokataan tapahtumaa, jossa on ilmoittautumisia
				$originalTextFields = Array();
				$originalMcFields = Array();
				// Tallenna alkuperäiset kentät ennen kuin formista tuleet tiedot luetaan event-muuttujaan
				foreach ($event->getFreeTextFields() as $field) $originalTextFields[] = $field;
				foreach ($event->getMultipleChoiceFields() as $field) $originalMcFields[] = $field;
			}
			
			
			
			$form->bindRequest($request);
			if($form->isValid()) {
				if($state == self::STATE_OLD_EVENT_NO_REGISTRANTS) {
					$originalEvent = $this->getDoctrine()
					->getRepository('ProdekoIlmoBundle:Event')
					->findOneBy(array('id'=>$event->getId()));
					foreach ($originalEvent->getQuotas() as $oldquota) {
						$em->remove($oldquota);
						$em->flush();
					}
				}
				$event = $form->getData();
				if ($state == self::STATE_OLD_EVENT_REGISTRANTS) {
					/* Kenttien lisäyksen ja poiston käsittely
					 Tämä on relevanttia vain jos muokataan tapahtumaa, jolla on ilmoittautumisia. */
				
					// Käytä helperseissä määriteltyä funktiota määrittämään lisätyt ja poistetut kentät.
					list($newTextFields, $deletedTextFields) = Helpers::filterFields($originalTextFields, $event->getFreeTextFields());
					list($newMcFields, $deletedMcFields) = Helpers::filterFields($originalMcFields, $event->getMultipleChoiceFields());
				
					// Lisää jokaiselle uudelle kentälle ilmoitukset kaikkiin olemassaoleviin ilmoittautumisiin, että kenttää ei ole täytetty.
					$em = Helpers::addDummyValues(array_merge($newTextFields, $newMcFields), $registrations, $em, "Ei täytetty");
				
					// Poista kaikkien poistuneiden fieldien entryt (ei poista fieldejä, koska fieldien kierrätys
					$em = Helpers::deleteEntries(array_merge($deletedMcFields, $deletedTextFields), $registrations, $em);
				}
				
				foreach($event->getQuotas() as $quota) {
					$quota->setEvent($event);
				}
				//Tallenna tapahtuma
				$em->persist($event);
				$em->flush();
				//Ohjaa tarkastelemaan luotua tapahtumaa
				return $this->redirect($this->generateUrl("show", array('id' => $event->getId())));
			}
			else {
				return $this->render('ProdekoIlmoBundle:Ilmo:createEvent.html.twig', array(
				'form' => $form->createView(),
				'id' => $id,
				'event' => $event
			));
			}
			
		}
		
		
		return $this->render('ProdekoIlmoBundle:Ilmo:createEvent.html.twig', array(
				'form' => $form->createView(),
				'id' => $id,
				'event' => $event
		));
	}
	
	/*
	 * Tuottaa adminia varten täydellisen listan ilmoittautuneista 
	 * kaikkine kenttäsyötteineen. 
	 */
	public function adminRegistrationsAction($id, Request $request)
	{
		$event = $this->getDoctrine()
			->getRepository('ProdekoIlmoBundle:Event')
			->findOneBy(array('id' => $id));
		if (!$event) { throw $this->createNotFoundException('Tapahtumaa ei löydy');}
		
		$registrations = $event->getRegistrations();
		$defaultData = array('sender' => 'ilmo@prodeko.fi');
		$form = $this->createFormBuilder($defaultData)
					 ->add('subject', 'text')
					 ->add('sender', 'text')
					 ->add('message', 'textarea')
					 ->getForm();
		
		
		return $this->render('ProdekoIlmoBundle:Ilmo:admin.html.twig', array(
				'event' => $event,
				'isOpen' => $event->registrationOpen(),
				'form'	=> $form->createView(), 
		));
		
	}
	
	public function sendEmailAction(Request $request, $id)
	{
		if ($request->getMethod() == 'POST') {
			$form = $this->createFormBuilder()
					 ->add('subject', 'text')
					 ->add('sender', 'text')
					 ->add('message', 'textarea')
					 ->getForm();
			$form->bindRequest($request);
			$data = $form->getData();
			$email = $data['sender'];
			$message = $data['message'];
			$subject = $data['subject'];
			
			$event = $this->getDoctrine()->getRepository('ProdekoIlmoBundle:Event')
								->find($id);
			$registrations = $event->getRegistrations(); //note this only gets the registrations that fit
			
			$emailMessage = \Swift_Message::newInstance();
			$emailMessage->setSubject($subject)
						 ->setSender($email)
						 ->setFrom($email)
						 ->setBody($message);
			foreach($registrations as $registration) {
				$emailMessage->addTo($registration->getEmail());
			}
			$this->get('mailer')->send($emailMessage);
			return $this->render('ProdekoIlmoBundle:Ilmo:admin.html.twig', array(
					'event' => $event,
					'isOpen' => $event->registrationOpen(),
					'form' => $form->createView(),
					'message' => 'Viesti lähetetty'));
		}
	}
	
	/*
	 * Tuottaa ilmoittautuneiden listasta CSV-tiedoston
	 * ladattavaksi.
	 */
	public function exportAction($id, Request $request)
	{
		$event = $this->getDoctrine()
			->getRepository('ProdekoIlmoBundle:Event')
			->findOneBy(array('id' => $id));
		$filename = $id . ".csv";
		$response = $this->render('ProdekoIlmoBundle:Ilmo:export.csv.twig', array('event' => $event));
		$response->headers->set('Content-Type', 'text/css');
		$response->headers->set('Content-Disposition', 'attachment; filename='.$filename);
		//muuta charset latin-15, jotta näkyy excelissa vaivoitta
		$response->setContent(mb_convert_encoding($response->getContent(), 'ISO-8859-15', 'UTF-8'));
		$response->setCharset('ISO-8859-15');
		return $response;
	}

	/*
	 * Renderöi admin-kirjautumislomakkeen
	 */
	public function loginAction()
	{
		$request = $this->getRequest();
		$session = $request->getSession();
		
		//Hae virheet, jos kirjautumislomakkeeseen palattiin virheen takia
		if($request->attributes->has(SecurityContext::AUTHENTICATION_ERROR)) {
			$error = $request->attributes->get(SecurityContext::AUTHENTICATION_ERROR);
		}
		else {
			$error = $session->get(SecurityContext::AUTHENTICATION_ERROR);
			$session->remove(SecurityContext::AUTHENTICATION_ERROR);
		}
		
		return $this->render('ProdekoIlmoBundle:Ilmo:login.html.twig', array(
					'last_username' => $session->get(SecurityContext::LAST_USERNAME),
					'error' => $error,));
	}
}
?>
