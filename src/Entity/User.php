<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="id")
     */
    private $id;
	
	/**
     * @ORM\Column(type="string", name="name", length=60)
     */
    private $name;
	
	/**
     * @ORM\Column(type="string", name="pwd", length=60)
     */
    private $pwd;

    /**
     * @ORM\Column(type="string", name="session_token", length=8)
     */
    private $session_token;
	
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
	
	public function getName() {
		$this->name;
	}
	
	public function getPwd() {
		$this->pwd;
	}
	
	public function getRow() {
		return [
			'id' => $this->id,
			'name' => $this->name,
			'pwd' => $this->pwd,
			'session_token' => $this->session_token,
		];
	}
}
