<?php

namespace App\Acme\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ankiety")
 * @ORM\Entity(repositoryClass="App\Acme\AdminBundle\Repository\SurveyRepository")
 */
class Survey
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="id")
     */
    private $id;
	
	/**
     * @ORM\Column(type="integer", name="uid")
     */
    private $uid;
	
	/**
     * @ORM\Column(type="string", name="imie", length=50)
     */
    private $name;
	
	/**
     * @ORM\Column(type="string", name="nazwisko", length=50)
     */
    private $surname;

    /**
     * @ORM\Column(type="integer", name="wiek")
     */
    private $age;
	
	public function __construct(array $values = []) {
		foreach ($values as $key => $value) $this->$key = $value;
	}
	
	public function set(string $key, $value) {
		$this->$key = $value;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function get(string $key) {
		return $this->$key;
	}
	
	public function getRow() {
		return [
			'id' => $this->id,
			'uid' => $this->uid,
			'name' => $this->name,
			'surname' => $this->surname,
			'age' => $this->age
		];
	}
}
