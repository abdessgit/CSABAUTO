<?php

namespace App\DataFixtures;

use App\Entity\Annonce;
use App\Entity\Conversation;
use App\Entity\Facture;
use App\Entity\Intervention;
use App\Entity\InterventionService;
use App\Entity\LigneFacture;
use App\Entity\Message;
use App\Entity\Photo;
use App\Entity\RendezVous;
use App\Entity\Service;
use App\Entity\Utilisateur;
use App\Entity\Vehicule;
use App\Enum\AnnonceStatut;
use App\Enum\ConversationStatut;
use App\Enum\FactureStatut;
use App\Enum\InterventionStatut;
use App\Enum\RendezVousStatut;
use App\Enum\UtilisateurRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // ===== UTILISATEURS =====

        $admin = $this->createUtilisateur($manager, 'Bouzaroura', 'Abdesslam', 'admin@csabauto.com', 'Test1234!', UtilisateurRole::ADMIN);

        $moderateur1 = $this->createUtilisateur($manager, 'Martin', 'Sophie', 'sophie.martin@csabauto.com', 'Test1234!', UtilisateurRole::MODERATEUR);
        $moderateur2 = $this->createUtilisateur($manager, 'Bernard', 'Karim', 'karim.bernard@csabauto.com', 'Test1234!', UtilisateurRole::MODERATEUR);

        $client1 = $this->createUtilisateur($manager, 'Dupont', 'Jean', 'jean.dupont@test.com', 'Test1234!', UtilisateurRole::CLIENT, '0612345678', '12 rue de Lille, 59000 Lille');
        $client2 = $this->createUtilisateur($manager, 'Durand', 'Marie', 'marie.durand@test.com', 'Test1234!', UtilisateurRole::CLIENT, '0623456789', '5 avenue de la République, 59000 Lille');
        $client3 = $this->createUtilisateur($manager, 'Lefevre', 'Paul', 'paul.lefevre@test.com', 'Test1234!', UtilisateurRole::CLIENT, '0634567890', '8 rue Nationale, 59800 Lille');

        // ===== VEHICULES =====

        $vehicule1 = (new Vehicule())
            ->setMarque('Peugeot')
            ->setModele('308')
            ->setAnnee(2019)
            ->setImmatriculation('AA-123-BB')
            ->setVin('VF3LCYHZPHS123456')
            ->setKilometrage(65000)
            ->setCouleur('Gris')
            ->setDateAjout(new \DateTimeImmutable('-6 months'))
            ->setProprietaire($client1);
        $manager->persist($vehicule1);

        $vehicule2 = (new Vehicule())
            ->setMarque('Renault')
            ->setModele('Clio')
            ->setAnnee(2021)
            ->setImmatriculation('CC-456-DD')
            ->setVin('VF1RJA00568123456')
            ->setKilometrage(28000)
            ->setCouleur('Bleu')
            ->setDateAjout(new \DateTimeImmutable('-3 months'))
            ->setProprietaire($client2);
        $manager->persist($vehicule2);

        $vehicule3 = (new Vehicule())
            ->setMarque('Volkswagen')
            ->setModele('Golf')
            ->setAnnee(2018)
            ->setImmatriculation('EE-789-FF')
            ->setVin('WVWZZZ1KZAW123456')
            ->setKilometrage(89000)
            ->setCouleur('Noir')
            ->setDateAjout(new \DateTimeImmutable('-1 year'))
            ->setProprietaire($client1);
        $manager->persist($vehicule3);

        $vehicule4 = (new Vehicule())
            ->setMarque('Citroën')
            ->setModele('C3')
            ->setAnnee(2020)
            ->setImmatriculation('GG-321-HH')
            ->setVin('VF7SXHMZ6LT123456')
            ->setKilometrage(41000)
            ->setCouleur('Blanc')
            ->setDateAjout(new \DateTimeImmutable('-2 months'))
            ->setProprietaire($client3);
        $manager->persist($vehicule4);

        // ===== SERVICES (catalogue du garage) =====

        $serviceVidange = (new Service())
            ->setNom('Vidange + filtre à huile')
            ->setDescription('Remplacement de l\'huile moteur et du filtre à huile')
            ->setPrixStandard('79.90')
            ->setDureeEstimee(45);
        $manager->persist($serviceVidange);

        $serviceFreins = (new Service())
            ->setNom('Remplacement plaquettes de frein')
            ->setDescription('Remplacement des plaquettes avant ou arrière')
            ->setPrixStandard('120.00')
            ->setDureeEstimee(60);
        $manager->persist($serviceFreins);

        $servicePneus = (new Service())
            ->setNom('Montage + équilibrage pneu')
            ->setDescription('Montage d\'un pneu neuf avec équilibrage')
            ->setPrixStandard('25.00')
            ->setDureeEstimee(20);
        $manager->persist($servicePneus);

        $serviceRevision = (new Service())
            ->setNom('Révision complète')
            ->setDescription('Contrôle des points de sécurité et des niveaux')
            ->setPrixStandard('149.00')
            ->setDureeEstimee(90);
        $manager->persist($serviceRevision);

        $serviceClim = (new Service())
            ->setNom('Recharge climatisation')
            ->setDescription('Contrôle et recharge du circuit de climatisation')
            ->setPrixStandard('69.00')
            ->setDureeEstimee(30);
        $manager->persist($serviceClim);

        // ===== ANNONCES + PHOTOS =====

        $annonce1 = (new Annonce())
            ->setTitre('Peugeot 308 - 2019 - Très bon état')
            ->setDescription('Véhicule entretenu régulièrement, courroie de distribution changée, carnet d\'entretien complet.')
            ->setPrix('11500.00')
            ->setStatut(AnnonceStatut::EN_VENTE)
            ->setDatePublication(new \DateTimeImmutable('-1 month'))
            ->setVehicule($vehicule1);
        $manager->persist($annonce1);

        $photo1 = (new Photo())->setUrl('/uploads/vehicules/peugeot-308-1.jpg')->setOrdreAffichage(1)->setAnnonce($annonce1);
        $photo2 = (new Photo())->setUrl('/uploads/vehicules/peugeot-308-2.jpg')->setOrdreAffichage(2)->setAnnonce($annonce1);
        $manager->persist($photo1);
        $manager->persist($photo2);

        $annonce2 = (new Annonce())
            ->setTitre('Volkswagen Golf - 2018 - Faible kilométrage')
            ->setDescription('Golf bien entretenue, révisée récemment, pneus neufs.')
            ->setPrix('13900.00')
            ->setStatut(AnnonceStatut::EN_VENTE)
            ->setDatePublication(new \DateTimeImmutable('-2 weeks'))
            ->setVehicule($vehicule3);
        $manager->persist($annonce2);

        $photo3 = (new Photo())->setUrl('/uploads/vehicules/golf-1.jpg')->setOrdreAffichage(1)->setAnnonce($annonce2);
        $manager->persist($photo3);

        // ===== RENDEZ-VOUS =====

        $rdv1 = (new RendezVous())
            ->setDateHeure(new \DateTimeImmutable('+3 days 09:00'))
            ->setMotif('Vidange + contrôle général')
            ->setStatut(RendezVousStatut::CONFIRME)
            ->setDateCreation(new \DateTimeImmutable('-2 days'))
            ->setClient($client2)
            ->setVehicule($vehicule2)
            ->setModerateur($moderateur1);
        $manager->persist($rdv1);

        $rdv2 = (new RendezVous())
            ->setDateHeure(new \DateTimeImmutable('+5 days 14:30'))
            ->setMotif('Bruit suspect au freinage')
            ->setStatut(RendezVousStatut::EN_ATTENTE)
            ->setDateCreation(new \DateTimeImmutable('-1 day'))
            ->setClient($client3)
            ->setVehicule($vehicule4)
            ->setModerateur(null);
        $manager->persist($rdv2);

        // ===== RENDEZ-VOUS TERMINÉ -> INTERVENTION -> FACTURE =====

        $rdv3 = (new RendezVous())
            ->setDateHeure(new \DateTimeImmutable('-10 days 10:00'))
            ->setMotif('Révision annuelle')
            ->setStatut(RendezVousStatut::TERMINE)
            ->setDateCreation(new \DateTimeImmutable('-15 days'))
            ->setClient($client1)
            ->setVehicule($vehicule1)
            ->setModerateur($moderateur2);
        $manager->persist($rdv3);

        $intervention1 = (new Intervention())
            ->setDateIntervention(new \DateTimeImmutable('-10 days'))
            ->setDescription('Révision complète + vidange effectuées, rien à signaler.')
            ->setKilometrageReleve(64500)
            ->setStatut(InterventionStatut::TERMINEE)
            ->setVehicule($vehicule1)
            ->setRendezVous($rdv3)
            ->setCoutTotal('228.90');
        $manager->persist($intervention1);

        $is1 = (new InterventionService())
            ->setIntervention($intervention1)
            ->setService($serviceRevision)
            ->setQuantite(1)
            ->setPrixApplique('149.00');
        $manager->persist($is1);

        $is2 = (new InterventionService())
            ->setIntervention($intervention1)
            ->setService($serviceVidange)
            ->setQuantite(1)
            ->setPrixApplique('79.90');
        $manager->persist($is2);

        $facture1 = (new Facture())
            ->setNumeroFacture('FACT-' . date('Ymd') . '-1001')
            ->setDateEmission(new \DateTimeImmutable('-10 days'))
            ->setMontantTotal('228.90')
            ->setStatut(FactureStatut::PAYEE)
            ->setIntervention($intervention1);
        $manager->persist($facture1);

        $ligne1 = (new LigneFacture())
            ->setFacture($facture1)
            ->setDescription('Révision complète')
            ->setQuantite(1)
            ->setPrixUnitaire('149.00')
            ->setSousTotal('149.00');
        $manager->persist($ligne1);

        $ligne2 = (new LigneFacture())
            ->setFacture($facture1)
            ->setDescription('Vidange + filtre à huile')
            ->setQuantite(1)
            ->setPrixUnitaire('79.90')
            ->setSousTotal('79.90');
        $manager->persist($ligne2);

        // ===== CONVERSATION + MESSAGES (liée à l'annonce 1) =====

        $conversation1 = (new Conversation())
            ->setDateCreation(new \DateTimeImmutable('-3 days'))
            ->setStatut(ConversationStatut::OUVERTE)
            ->setClient($client2)
            ->setModerateur($moderateur1)
            ->setAnnonce($annonce1);
        $manager->persist($conversation1);

        $message1 = (new Message())
            ->setContenu('Bonjour, la Peugeot 308 est-elle toujours disponible ?')
            ->setDateEnvoi(new \DateTimeImmutable('-3 days'))
            ->setLu(true)
            ->setConversation($conversation1)
            ->setExpediteur($client2);
        $manager->persist($message1);

        $message2 = (new Message())
            ->setContenu('Bonjour, oui elle est toujours disponible. Souhaitez-vous un essai ?')
            ->setDateEnvoi(new \DateTimeImmutable('-3 days +1 hour'))
            ->setLu(true)
            ->setConversation($conversation1)
            ->setExpediteur($moderateur1);
        $manager->persist($message2);

        $message3 = (new Message())
            ->setContenu('Oui avec plaisir, êtes-vous disponible ce week-end ?')
            ->setDateEnvoi(new \DateTimeImmutable('-2 days'))
            ->setLu(false)
            ->setConversation($conversation1)
            ->setExpediteur($client2);
        $manager->persist($message3);

        $manager->flush();
    }

    private function createUtilisateur(
        ObjectManager $manager,
        string $nom,
        string $prenom,
        string $email,
        string $password,
        UtilisateurRole $role,
        ?string $telephone = null,
        ?string $adresse = null,
    ): Utilisateur {
        $utilisateur = (new Utilisateur())
            ->setNom($nom)
            ->setPrenom($prenom)
            ->setEmail($email)
            ->setTelephone($telephone)
            ->setAdresse($adresse)
            ->setDateCreation(new \DateTimeImmutable());

        // Adapte cette ligne selon le nom exact du setter généré pour les rôles
        // (ex: setRoleEnums([...]) comme vu dans UtilisateurController, ou setRoles([...]))
        $utilisateur->setRoleEnums([$role]);
        $utilisateur->setPassword($this->hasher->hashPassword($utilisateur, $password));

        $manager->persist($utilisateur);

        return $utilisateur;
    }
}