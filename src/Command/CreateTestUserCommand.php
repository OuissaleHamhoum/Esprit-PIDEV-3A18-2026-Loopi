<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-test-user',
    description: 'Create a test user in the database',
)]
class CreateTestUserCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $user = new User();
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setEmail('testuser@loopi.tn');
        $user->setPassword('$2y$13$vpzGhBwiBIyxTZ4M3AxgAeeOueds6H7E6mSuxY0uIjVNUc0r/lHWu'); // 'password'
        $user->setPhoto('default.jpg');
        $user->setRole('participant');
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Test user created: testuser@loopi.tn / password');
        return Command::SUCCESS;
    }
}
