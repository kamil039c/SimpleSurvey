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
		
		$names = ["mariusz","mateusz","franio","bogusław","mietek","wiesio","łukasz","sebastian"];
		$surnames = ["kowalski","nowak","cieplak","marczuk","kowalik","niegłowski","ćwierkacz"];
		
		for ($i = 0; $i < 50; $i++) {
			$manager->persist(
				new Survey([
					'uid' => mt_rand(1, $countUsers), 
					'name' => $names[mt_rand(0, count($names) - 1)],
					'surname' => $surnames[mt_rand(0, count($surnames) - 1)],
					'age' => mt_rand(10,90)
				])
			);
		}
		
		$manager->flush();
    }
}
?>