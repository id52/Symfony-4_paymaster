<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * @ORM\Table(name="invoice")
 * @ORM\Entity(repositoryClass="App\Repository\InvoiceRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Invoice
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $phone;

    /**
     * @return mixed
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param mixed $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string")
     */
    private $number;

    /**
     * @return mixed
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param mixed $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $commentary;

    /**
     * @return mixed
     */
    public function getCommentary()
    {
        return $this->commentary;
    }

    /**
     * @param mixed $commentary
     */
    public function setCommentary($commentary)
    {
        $this->commentary = $commentary;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }


    public function __toString()
    {
        return $this->title;
    }

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="users")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;
    }

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
     * @var datetime $created_at
     *
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * Gets triggered only on insert
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->created_at = new \DateTime("now");
    }

    /**
     * @var datetime $paid_at
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $paid_at;

    /**
     * @return datetime
     */
    public function getPaidAt()
    {
        return $this->paid_at;
    }

    /**
     * @param datetime $paid_at
     */
    public function setPaidAt($paid_at)
    {
        $this->paid_at = $paid_at;
    }

    /**
     * @return datetime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param datetime $created_at
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
    }




    /**
     * @ORM\ManyToOne(targetEntity="Status", inversedBy="invoices")
     * @ORM\JoinColumn(name="status_id", referencedColumnName="id")
     */
    private $status;

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }


    /**
     * @ORM\Column(type="decimal", scale=2)
     */
    private $sum;

    /**
     * @return mixed
     */
    public function getSum()
    {
        $english_format_number = number_format($this->sum, 2, '.', '');
        if ($english_format_number == '0.00') {
            return $this->sum;
        }
        return $english_format_number;
    }

    /**
     * @param mixed $sum
     */
    public function setSum($sum)
    {
        $this->sum = $sum;
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param mixed $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $uri;

}