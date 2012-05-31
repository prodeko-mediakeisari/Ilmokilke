<?php

namespace Prodeko\IlmoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Prodeko\IlmoBundle\Entity\Registration
 */
class Registration
{
    /**
     * @var integer $id
     */
    private $id;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * @var datetime $registration_time
     */
    private $registration_time;


    /**
     * Set registration_time
     *
     * @param datetime $registrationTime
     */
    public function setRegistrationTime($registrationTime)
    {
        $this->registration_time = $registrationTime;
    }

    /**
     * Get registration_time
     *
     * @return datetime 
     */
    public function getRegistrationTime()
    {
        return $this->registration_time;
    }
    /**
     * @var Prodeko\IlmoBundle\Entity\Person
     */
    private $person;


    /**
     * Set person
     *
     * @param Prodeko\IlmoBundle\Entity\Person $person
     */
    public function setPerson(\Prodeko\IlmoBundle\Entity\Person $person)
    {
        $this->person = $person;
    }

    /**
     * Get person
     *
     * @return Prodeko\IlmoBundle\Entity\Person 
     */
    public function getPerson()
    {
        return $this->person;
    }
    /**
     * @var Prodeko\IlmoBundle\Entity\Event
     */
    private $forEvent;


    /**
     * Set forEvent
     *
     * @param Prodeko\IlmoBundle\Entity\Event $forEvent
     */
    public function setForEvent(\Prodeko\IlmoBundle\Entity\Event $forEvent)
    {
        $this->forEvent = $forEvent;
    }

    /**
     * Get forEvent
     *
     * @return Prodeko\IlmoBundle\Entity\Event 
     */
    public function getForEvent()
    {
        return $this->forEvent;
    }
    /**
     * @var Prodeko\IlmoBundle\Entity\Event
     */
    private $event;


    /**
     * Set event
     *
     * @param Prodeko\IlmoBundle\Entity\Event $event
     */
    public function setEvent(\Prodeko\IlmoBundle\Entity\Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get event
     *
     * @return Prodeko\IlmoBundle\Entity\Event 
     */
    public function getEvent()
    {
        return $this->event;
    }
    /**
     * @var string $firstName
     */
    private $firstName;

    /**
     * @var string $lastName
     */
    private $lastName;

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string $allergies
     */
    private $allergies;


    /**
     * Set firstName
     *
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Get firstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Get lastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set allergies
     *
     * @param string $allergies
     */
    public function setAllergies($allergies)
    {
        $this->allergies = $allergies;
    }

    /**
     * Get allergies
     *
     * @return string 
     */
    public function getAllergies()
    {
        return $this->allergies;
    }
    /**
     * @var Prodeko\IlmoBundle\Entity\FreeTextEntry
     */
    private $freeTextEntries;

    public function __construct()
    {
        $this->freeTextEntries = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Add freeTextEntries
     *
     * @param Prodeko\IlmoBundle\Entity\FreeTextEntry $freeTextEntries
     */
    public function addFreeTextEntry(\Prodeko\IlmoBundle\Entity\FreeTextEntry $freeTextEntries)
    {
        $this->freeTextEntries[] = $freeTextEntries;
    }

    /**
     * Get freeTextEntries
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getFreeTextEntries()
    {
        return $this->freeTextEntries;
    }
    /**
     * @var datetime $registrationTime
     */
    private $registrationTime;


}