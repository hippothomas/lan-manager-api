<?php

namespace App\DataFixtures;

use DateTime;
use Faker\Factory;
use App\Entity\User;
use App\Entity\LANParty;
use App\Entity\Registration;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('FR-fr');

		// Generating Users
		$users = [];
		for ($i=0; $i < 20; $i++) {
			$user = new User();
			$user->setUsername($faker->userName());
			$user->setPassword($faker->password());
			$user->setRoles(["ROLE_USER"]);
			$manager->persist($user);
			$users[] = $user;
		}

		// Generating LANParties
		$lanparties = [];
		for ($i=0; $i < 3; $i++) {
			$lanparty = new LANParty();
			$lanparty->setName($faker->word());
			$lanparty->setMaxPlayers($faker->numberBetween(2, 300));
			$lanparty->setPrivate($faker->boolean());
			$lanparty->setRegistrationOpen($faker->boolean());
			$lanparty->setLocation($faker->address());
			$lanparty->setCoverImage("https://picsum.photos/id/".mt_rand(1, 99)."/200/300");
			$lanparty->setWebsite($faker->url());
			$lanparty->setCost($faker->randomFloat(2, 10, 250));
			$lanparty->setDescription($faker->paragraph());
			$lanparty->setDateStart($faker->dateTimeBetween('now', '+1 year'));
			$lanparty->setDateEnd($faker->dateTimeInInterval($lanparty->getDateStart(), '+1 week'));
			$manager->persist($lanparty);
			$lanparties[] = $lanparty;
		}

		// Generating Registrations for each user
		foreach ($users as $user) {
			$registration = new Registration();
			$registration->setAccount($user);
			$registration->setLanParty($lanparties[mt_rand(0, count($lanparties)-1)]);
			$registration->setRoles(["PLAYER"]);
			$registration->setStatus("registered");
			$manager->persist($registration);
		}

        $manager->flush();
    }
}
