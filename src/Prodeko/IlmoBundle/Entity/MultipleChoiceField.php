<?php

namespace Prodeko\IlmoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Prodeko\IlmoBundle\Entity\MultipleChoiceField
 */
class MultipleChoiceField
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var array $choices
     */
    private $choices;
    
    /**
     * @var boolean $flagPrivate
     */
    private $flagPrivate = true;
    
    /**
     * @var Prodeko\IlmoBundle\Entity\MultipleChoiceEntry
     */
    private $entries;
    
    /**
     * @var Prodeko\IlmoBundle\Entity\Event
     */
    private $events;
    
    
    public function __construct()
    {
    	$this->entries = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    
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
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return stripslashes($this->name);
    }

    /**
     * Set choices
     *
     * @param array $choices
     */
    public function setChoices($choices)
    {
        $this->choices = $choices;
    }

    /**
     * Get choices
     *
     * @return array 
     */
    public function getChoices()
    {
        return $this->choices;
    }
    
    /**
     * Set flagPrivate
     *
     * @param boolean $flagPrivate
     */
    public function setFlagPrivate($flagPrivate)
    {
    	$this->flagPrivate = $flagPrivate;
    }
    
    /**
     * Get flagPrivate
     *
     * @return boolean
     */
    public function getFlagPrivate()
    {
    	return $this->flagPrivate;
    }
    
    /**
     * Add entries
     *
     * @param Prodeko\IlmoBundle\Entity\MultipleChoiceEntry $entries
     */
    public function addMultipleChoiceEntry(\Prodeko\IlmoBundle\Entity\MultipleChoiceEntry $entries)
    {
    	$this->entries[] = $entries;
    }
    
    /**
     * Get entries
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getEntries()
    {
    	return $this->entries;
    }
    
    /**
     * Add events
     *
     * @param Prodeko\IlmoBundle\Entity\Event $events
     */
    public function addEvent(\Prodeko\IlmoBundle\Entity\Event $events)
    {
    	$this->events[] = $events;
    }
    
    /**
     * Get events
     *
     * @return Doctrine\Common\Collections\Collection
     */
    public function getEvents()
    {
    	return $this->events;
    }
    
    /**
     * Set events
     *
     * @param $events
     */
    public function setEvents( $events)
    {
    	$this->events = $events;
    }



}