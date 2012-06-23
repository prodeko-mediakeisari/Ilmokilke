<?php 
namespace Prodeko\IlmoBundle\Controller;


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
	
	// Näyttää lomakkeen jolla luodaan tapahtuma
	public function createEventFormAction($id, Request $request) 
	{
		// $state-muuttujan arvot siis: 1=Uuden tapahtuman luonti, 2=Tapahtuman muokkaus, ei vielä ilmoittautuneita, 3=Tapahtuman muokkaus, ilmoittautumisia jo tullut.
		if ($id != 0) {
			$event = $this->getDoctrine()
			->getRepository('ProdekoIlmoBundle:Event')
			->findOneBy(array('id' => $id));
			if ($event) {
				$registrations = $event->getRegistrations();
				if ($registrations && count($registrations) > 0) {
					$state = 3; // Sellaisen tapahtuman muokkaus, jolla on ilmoittautumisia.
				} else {
					$state = 2; // Muokkaus, ei ilmoittautumisia.
				}
			} else {
				throw $this->createNotFoundException('Tapahtumaa ei löydy');
				//echo "Ei löydy tapahtumaa!"; die; //TODO: Tähän ehkä joku exception handling
			}
		}
		else {
			$state = 1; // Uuden tapahtuman luonti.
			$event = new Event();
			$quota_names = array('I','II','III','IV','N');
			
			for($i=1;$i<=5;$i++) {
				$quota = new Quota();
				$quota->setYearOfStudiesValue($i);
				$quota->setName($quota_names[$i-1]);
				$quota->setEvent($event);
				$event->addQuota($quota);
			}
				
		}
		
		//Tee ilmoittautumislomake, määrittely löytyy Prodeko\IlmoBundle\Form\Type\EventType
		$form = $this->createForm(new EventType(), $event);
		
		//Jos kyseessä on lomakkeen käsittely, eikä lomakkeen näyttö
		if ($request->getMethod() == 'POST') {
			
			$em = $this->getDoctrine()->getEntityManager();
			
			if ($state == 3) { // Eli jos muokataan tapahtumaa, jossa on ilmoittautumisia
				$originalTextFields = Array();
				$originalMcFields = Array();
				// Tallenna alkuperäiset kentät ennen kuin formista tuleet tiedot luetaan event-muuttujaan
				foreach ($event->getFreeTextFields() as $field) $originalTextFields[] = $field;
				foreach ($event->getMultipleChoiceFields() as $field) $originalMcFields[] = $field;
			}
			
			
			//TODO: if ($form->isValid()) { ... } Jutut tähän väliin.
			
			//Anna lomakkeesta tulleet arvot eventille
			$form->bindRequest($request);
			$event = $form->getData();
			
			
			if ($state == 3) { 
				 /* Kenttien lisäyksen ja poiston käsittely
				 	Tämä on relevanttia vain jos muokataan tapahtumaa, jolla on ilmoittautumisia. */
				
				// Käytä helperseissä määriteltyä funktiota määrittämään lisätyt ja poistetut kentät.
				list($newTextFields, $deletedTextFields) = Helpers::filterFields($originalTextFields, $event->getFreeTextFields());
				list($newMcFields, $deletedMcFields) = Helpers::filterFields($originalMcFields, $event->getMultipleChoiceFields());
				
				// Lisää jokaiselle uudelle kentälle ilmoitukset kaikkiin olemassaoleviin ilmoittautumisiin, että kenttää ei ole täytetty.
				$em = Helpers::addDummyValues(array_merge($newTextFields, $newMcFields), $registrations, $em, "Ei täytetty");
				
				// Poista kaikkien poistuneiden fieldien entryt (ei poista fieldejä, koska fieldien kierrätys
				$em = Helpers::deleteEntries(array_merge($deletedMcFields, $deletedTextFields), $registrations, $em);
				
			} // Lopeta kenttien lisäyksen ja poiston käsittely
			
			//Tallenna tapahtuma
			$em->persist($event);
			$em->flush();
			//Ohjaa tarkastelemaan luotua tapahtumaa
			return $this->redirect($this->generateUrl("show", array('id' => $event->getId())));
		}
		
		
		return $this->render('ProdekoIlmoBundle:Ilmo:createEvent.html.twig', array(
				'form' => $form->createView(), 'id' => $id
		));
	}
	
	public function adminRegistrationsAction($id, Request $request) // Täydellinen lista ilmoittautuneista admineille
	{
		$event = $this->getDoctrine()
			->getRepository('ProdekoIlmoBundle:Event')
			->findOneBy(array('id' => $id));
		if (!$event) { throw $this->createNotFoundException('Tapahtumaa ei löydy');} // TODO: joku parempi exceptionhandlaus näihin.
		
		$registrations = $event->getRegistrations();
		
		return $this->render('ProdekoIlmoBundle:Ilmo:adminRegistrations.html.twig', array(
				'event' => $event,
				'registrations' => $registrations,
				'isOpen' => $event->isOpen()
		));
		
	}
	
}
?>
