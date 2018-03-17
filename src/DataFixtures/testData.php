<?php
namespace App\DataFixtures;

use App\Entity\Survey;
use App\Entity\User;
use App\Utils\PasswordUtil;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Driver\Connection;

class testData extends Fixture
{
    public function load(ObjectManager $manager)
    {
		//Struktura db:
		/*
		try {
			$this->db->beginTransaction();
			
			$this->db->exec("CREATE TABLE `ankiety` (
				`id` INTEGER AUTO_INCREMENT,
				`uid` INTEGER,
				`imie` varchar(50),
				`nazwisko` varchar(50),
				`wiek` INTEGER
			)");
		
			$this->db->exec("CREATE TABLE `users` (
				`id` INTEGER AUTO_INCREMENT,
				`name` varchar(50),
				`pwd` varchar(60),
				`session_token` varchar(8) DEFAULT 'abcd7654'
			)");
			$this->db->commit();
		} catch(PDOException $e) {
			die('B³¹d: ' . $e->getMessage());
		}
		*/
		
		//Dodaj testowych userów:
		$pwd = PasswordUtil::hashPw('123456');
		$st = 'gqds1287';
		
		$users = [new User(['name' => 'kamil', 'pwd' => $pwd, 'session_token' => $st])];
		$users[] = new User(['franek' => 'kamil', 'pwd' => $pwd, 'session_token' => $st]);
		$users[] = new User(['marian' => 'kamil', 'pwd' => $pwd, 'session_token' => $st]);
		$users[] = new User(['malgoska' => 'kamil', 'pwd' => $pwd, 'session_token' => $st]);
		$users[] = new User(['kaska' => 'kamil', 'pwd' => $pwd, 'session_token' => $st]);
		$users[] = new User(['mateusz' => 'kamil', 'pwd' => $pwd, 'session_token' => $st]);
		$users[] = new User(['mateusz' => 'adam', 'pwd' => $pwd, 'session_token' => $st]);
		foreach ($users as $user) $manager->persist($user);
		
		$countUsers = count($users);
		
		//Dodaj testowe ankiety
		$imiona = ["mariusz","mateusz","franio","bogus³aw","mietek","wiesio","³ukasz","sebastian"];
		$nazwiska = ["kowalski","nowak","cieplak","marczuk","kowalik","nieg³owski","æwierkacz"];
		
		for ($i = 0; $i < 50; $i++) {
			$manager->persist(
				new Survey([
					'uid' => mt_rand(1, $countUsers), 
					'name' => $imiona[mt_rand(0, count($imiona) - 1)],
					'surname' => $nazwiska[mt_rand(0, count($nazwiska) - 1)],
					'age' => mt_rand(10,90)
				])
			);
		}
		
		$manager->flush();
    }
}
?>