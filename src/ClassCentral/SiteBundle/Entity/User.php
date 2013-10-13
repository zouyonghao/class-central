<?php

namespace ClassCentral\SiteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Users
 * @UniqueEntity(
 *  fields = "email",
 *  message = "An account with this email address already exists"
 * )
 */
class User implements UserInterface, \Serializable
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var email
     */
    private $email;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $role;

    /**
     * @var boolean
     */
    private $isActive;

    private $salt;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $created;

    /**
     * @var \DateTime
     */
    private $modified;

    /**
     * @var \DateTime
     */
    private $lastLogin;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $moocTrackerSearchTerms;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $moocTrackerCourses;


    public function  __construct()
    {
        $this->role = "ROLE_STUDENT";
        $this->isActive = true;
        $this->setCreated(new \DateTime());
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
     * Set email
     *
     * @param string $email
     * @return Users
     */
    public function setEmail($email)
    {
        $this->email = $email;
    
        return $this;
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
     * Set password
     *
     * @param string $password
     * @return Users
     */
    public function setPassword($password)
    {
        $this->password = $password;
    
        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set roles
     *
     * @param string $roles
     * @return Users
     */
    public function setRole($role)
    {
        $this->role = $role;
    
        return $this;
    }

    /**
     * Get role
     *
     * @return string 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Users
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;
    
        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * @var string
     */
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
        ));
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            ) = unserialize($serialized);
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string The salt
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->getEmail();
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     *
     * @return void
     */
    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        return array($this->getRole());
    }


    /**
     * Set name
     *
     * @param string $name
     * @return User
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return User
     */
    public function setCreated($created)
    {
        $this->created = $created;
    
        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set modified
     *
     * @param \DateTime $modified
     * @return User
     */
    public function setModified($modified)
    {
        $this->modified = $modified;
    
        return $this;
    }

    /**
     * Get modified
     *
     * @return \DateTime 
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * Set lastLogin
     *
     * @param \DateTime $lastLogin
     * @return User
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    
        return $this;
    }

    /**
     * Get lastLogin
     *
     * @return \DateTime 
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }


    /**
     * Add moocTrackerSearchTerms
     *
     * @param \ClassCentral\SiteBundle\Entity\MoocTrackerSearchTerm $moocTrackerSearchTerms
     * @return User
     */
    public function addMoocTrackerSearchTerm(\ClassCentral\SiteBundle\Entity\MoocTrackerSearchTerm $moocTrackerSearchTerms)
    {
        $this->moocTrackerSearchTerms[] = $moocTrackerSearchTerms;
    
        return $this;
    }

    /**
     * Remove moocTrackerSearchTerms
     *
     * @param \ClassCentral\SiteBundle\Entity\MoocTrackerSearchTerm $moocTrackerSearchTerms
     */
    public function removeMoocTrackerSearchTerm(\ClassCentral\SiteBundle\Entity\MoocTrackerSearchTerm $moocTrackerSearchTerms)
    {
        $this->moocTrackerSearchTerms->removeElement($moocTrackerSearchTerms);
    }

    /**
     * Get moocTrackerSearchTerms
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMoocTrackerSearchTerms()
    {
        return $this->moocTrackerSearchTerms;
    }

    /**
     * Add moocTrackerCourses
     *
     * @param \ClassCentral\SiteBundle\Entity\MoocTrackerCourse $moocTrackerCourses
     * @return User
     */
    public function addMoocTrackerCourse(\ClassCentral\SiteBundle\Entity\MoocTrackerCourse $moocTrackerCourses)
    {
        $this->moocTrackerCourses[] = $moocTrackerCourses;
    
        return $this;
    }

    /**
     * Remove moocTrackerCourses
     *
     * @param \ClassCentral\SiteBundle\Entity\MoocTrackerCourse $moocTrackerCourses
     */
    public function removeMoocTrackerCourse(\ClassCentral\SiteBundle\Entity\MoocTrackerCourse $moocTrackerCourses)
    {
        $this->moocTrackerCourses->removeElement($moocTrackerCourses);
    }

    /**
     * Get moocTrackerCourses
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMoocTrackerCourses()
    {
        return $this->moocTrackerCourses;
    }
}