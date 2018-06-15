<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * @ORM\Table(name="status")
 * @ORM\Entity(repositoryClass="App\Repository\StatusRepository")
 */
class Status
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ORM\Column(type="string", unique=true)
     */
    private $title;

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @ORM\OneToMany(targetEntity="Invoice", mappedBy="status")
     */
    private $invoices;

    /**
     * @return ArrayCollection
     */
    public function getStatuses()
    {
        return $this->statuses;
    }

    /**
     * @param ArrayCollection $statuses
     */
    public function setStatuses($statuses)
    {
        $this->statuses = $statuses;
    }


    public function __construct()
    {
        $this->statuses = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->title;
    }


}