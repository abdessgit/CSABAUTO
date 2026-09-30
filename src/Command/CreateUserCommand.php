<?php

namespace App\Command;

use App\Entity\Utilisateur;
use App\Enum\UtilisateurRole;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Crée un utilisateur avec le rôle spécifié (ex: ROLE_ADMIN, ROLE_MODERATEUR, ROLE_CLIENT).',
    aliases: ['app:create-admin']
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UtilisateurRepository $userRepo,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Adresse e-mail de l\'utilisateur')
            ->addArgument('nom', InputArgument::OPTIONAL, 'Nom')
            ->addArgument('prenom', InputArgument::OPTIONAL, 'Prénom')
            ->addArgument('role', InputArgument::OPTIONAL, 'Rôle (ROLE_ADMIN, ROLE_MODERATEUR, ROLE_CLIENT)')
            ->addArgument('password', InputArgument::OPTIONAL, 'Mot de passe');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isCreateAdminAlias = ($input->getFirstArgument() === 'app:create-admin');

        $email = $input->getArgument('email');
        if (!$email) {
            $email = $io->ask('Adresse e-mail');
        }
        $email = strtolower(trim((string) $email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('L\'adresse e-mail n\'est pas valide.');
            return Command::FAILURE;
        }

        if ($this->userRepo->findOneBy(['email' => $email])) {
            $io->error(sprintf('L\'adresse e-mail "%s" est déjà utilisée.', $email));
            return Command::FAILURE;
        }

        $prenom = $input->getArgument('prenom');
        if (!$prenom) {
            $prenom = $io->ask('Prénom', 'Admin');
        }
        $prenom = trim((string) $prenom);

        $nom = $input->getArgument('nom');
        if (!$nom) {
            $nom = $io->ask('Nom', 'CSAB');
        }
        $nom = trim((string) $nom);

        $roleArg = $input->getArgument('role');
        if (!$roleArg) {
            $roleArg = $isCreateAdminAlias
                ? 'ROLE_ADMIN'
                : $io->choice('Rôle', ['ROLE_ADMIN', 'ROLE_MODERATEUR', 'ROLE_CLIENT'], 'ROLE_ADMIN');
        }
        $roleArg = strtoupper(trim((string) $roleArg));

        $cleanRole = str_starts_with($roleArg, 'ROLE_') ? substr($roleArg, 5) : $roleArg;
        $role = UtilisateurRole::tryFrom($cleanRole);

        if (!$role) {
            $io->error(sprintf('Rôle invalide "%s". Rôles acceptés : ROLE_ADMIN, ROLE_MODERATEUR, ROLE_CLIENT.', $roleArg));
            return Command::FAILURE;
        }

        $password = $input->getArgument('password');
        if (!$password) {
            $password = $io->askHidden('Mot de passe (au moins 8 caractères)');
        }
        $password = (string) $password;

        if (strlen($password) < 8) {
            $io->error('Le mot de passe doit comporter au moins 8 caractères.');
            return Command::FAILURE;
        }

        $u = (new Utilisateur())
            ->setEmail($email)
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setRoleEnums([$role])
            ->setDateCreation(new \DateTimeImmutable())
            ->setIsVerified(true)
            ->setEmailVerifiedAt(new \DateTimeImmutable());

        $u->setPassword($this->hasher->hashPassword($u, $password));

        $this->em->persist($u);
        $this->em->flush();

        $io->success(sprintf('Utilisateur "%s" créé avec succès avec le rôle %s (ID: %d).', $email, $role->name, $u->getId()));

        return Command::SUCCESS;
    }
}
