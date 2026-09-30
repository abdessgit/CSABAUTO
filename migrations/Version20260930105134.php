<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260930105134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE annonce (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, marque VARCHAR(100) NOT NULL, modele VARCHAR(100) NOT NULL, annee INT NOT NULL, kilometrage INT NOT NULL, prix NUMERIC(10, 2) NOT NULL, carburant VARCHAR(50) DEFAULT NULL, boite VARCHAR(50) DEFAULT NULL, puissance INT DEFAULT NULL, couleur VARCHAR(50) DEFAULT NULL, nb_portes INT DEFAULT NULL, nb_places INT DEFAULT NULL, description LONGTEXT DEFAULT NULL, photos JSON DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_publication DATETIME DEFAULT NULL, date_creation DATETIME NOT NULL, vehicule_id INT DEFAULT NULL, INDEX IDX_F65593E54A4A3511 (vehicule_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE conversation (id INT AUTO_INCREMENT NOT NULL, objet VARCHAR(120) DEFAULT NULL, date_creation DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, client_id INT NOT NULL, moderateur_id INT DEFAULT NULL, annonce_id INT DEFAULT NULL, INDEX IDX_8A8E26E919EB6921 (client_id), INDEX IDX_8A8E26E920A01F78 (moderateur_id), INDEX IDX_8A8E26E98805AB2F (annonce_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE email_verification_token (id INT AUTO_INCREMENT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_TOKEN_HASH (token_hash), INDEX IDX_C4995C67A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE facture (id INT AUTO_INCREMENT NOT NULL, numero_facture VARCHAR(50) NOT NULL, date_emission DATE NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, statut VARCHAR(20) NOT NULL, intervention_id INT NOT NULL, UNIQUE INDEX UNIQ_FE8664108EAE3863 (intervention_id), UNIQUE INDEX UNIQ_FACTURE_NUMERO (numero_facture), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE intervention (id INT AUTO_INCREMENT NOT NULL, date_intervention DATE NOT NULL, description LONGTEXT DEFAULT NULL, kilometrage_releve INT DEFAULT NULL, cout_total NUMERIC(10, 2) NOT NULL, statut VARCHAR(20) NOT NULL, vehicule_id INT NOT NULL, rendez_vous_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_D11814AB91EF7EAA (rendez_vous_id), INDEX IDX_D11814AB4A4A3511 (vehicule_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE intervention_service (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, prix_applique NUMERIC(10, 2) NOT NULL, intervention_id INT NOT NULL, service_id INT NOT NULL, INDEX IDX_AA73EEB78EAE3863 (intervention_id), INDEX IDX_AA73EEB7ED5CA9E6 (service_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ligne_facture (id INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, sous_total NUMERIC(10, 2) NOT NULL, facture_id INT NOT NULL, INDEX IDX_611F5A297F2DEE08 (facture_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, lu TINYINT DEFAULT 0 NOT NULL, conversation_id INT NOT NULL, expediteur_id INT NOT NULL, INDEX IDX_B6BD307F9AC0396 (conversation_id), INDEX IDX_B6BD307F10335F61 (expediteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE password_reset_token (id INT AUTO_INCREMENT NOT NULL, token_hash VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, request_ip VARCHAR(45) DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_RESET_TOKEN_HASH (token_hash), INDEX IDX_6B7BA4B6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE photo (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(500) NOT NULL, ordre_affichage INT DEFAULT NULL, annonce_id INT NOT NULL, INDEX IDX_14B784188805AB2F (annonce_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rendez_vous (id INT AUTO_INCREMENT NOT NULL, date_heure DATETIME NOT NULL, motif VARCHAR(255) DEFAULT NULL, statut VARCHAR(20) NOT NULL, date_creation DATETIME NOT NULL, client_id INT NOT NULL, vehicule_id INT NOT NULL, moderateur_id INT DEFAULT NULL, INDEX IDX_65E8AA0A19EB6921 (client_id), INDEX IDX_65E8AA0A4A4A3511 (vehicule_id), INDEX IDX_65E8AA0A20A01F78 (moderateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE service (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, prix_standard NUMERIC(10, 2) NOT NULL, duree_estimee INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, password VARCHAR(255) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, adresse VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, date_creation DATETIME NOT NULL, is_verified TINYINT DEFAULT 0 NOT NULL, email_verified_at DATETIME DEFAULT NULL, password_changed_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_UTILISATEUR_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vehicule (id INT AUTO_INCREMENT NOT NULL, marque VARCHAR(100) NOT NULL, modele VARCHAR(100) NOT NULL, annee INT NOT NULL, immatriculation VARCHAR(20) NOT NULL, vin VARCHAR(50) DEFAULT NULL, kilometrage INT NOT NULL, couleur VARCHAR(50) DEFAULT NULL, date_ajout DATETIME NOT NULL, proprietaire_id INT NOT NULL, UNIQUE INDEX UNIQ_VEHICULE_IMMATRICULATION (immatriculation), INDEX IDX_292FFF1D76C50E4A (proprietaire_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E54A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E919EB6921 FOREIGN KEY (client_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E920A01F78 FOREIGN KEY (moderateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE conversation ADD CONSTRAINT FK_8A8E26E98805AB2F FOREIGN KEY (annonce_id) REFERENCES annonce (id)');
        $this->addSql('ALTER TABLE email_verification_token ADD CONSTRAINT FK_C4995C67A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE8664108EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB4A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id)');
        $this->addSql('ALTER TABLE intervention ADD CONSTRAINT FK_D11814AB91EF7EAA FOREIGN KEY (rendez_vous_id) REFERENCES rendez_vous (id)');
        $this->addSql('ALTER TABLE intervention_service ADD CONSTRAINT FK_AA73EEB78EAE3863 FOREIGN KEY (intervention_id) REFERENCES intervention (id)');
        $this->addSql('ALTER TABLE intervention_service ADD CONSTRAINT FK_AA73EEB7ED5CA9E6 FOREIGN KEY (service_id) REFERENCES service (id)');
        $this->addSql('ALTER TABLE ligne_facture ADD CONSTRAINT FK_611F5A297F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversation (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE password_reset_token ADD CONSTRAINT FK_6B7BA4B6A76ED395 FOREIGN KEY (user_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE photo ADD CONSTRAINT FK_14B784188805AB2F FOREIGN KEY (annonce_id) REFERENCES annonce (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A19EB6921 FOREIGN KEY (client_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A4A4A3511 FOREIGN KEY (vehicule_id) REFERENCES vehicule (id)');
        $this->addSql('ALTER TABLE rendez_vous ADD CONSTRAINT FK_65E8AA0A20A01F78 FOREIGN KEY (moderateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE vehicule ADD CONSTRAINT FK_292FFF1D76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annonce DROP FOREIGN KEY FK_F65593E54A4A3511');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E919EB6921');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E920A01F78');
        $this->addSql('ALTER TABLE conversation DROP FOREIGN KEY FK_8A8E26E98805AB2F');
        $this->addSql('ALTER TABLE email_verification_token DROP FOREIGN KEY FK_C4995C67A76ED395');
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE8664108EAE3863');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB4A4A3511');
        $this->addSql('ALTER TABLE intervention DROP FOREIGN KEY FK_D11814AB91EF7EAA');
        $this->addSql('ALTER TABLE intervention_service DROP FOREIGN KEY FK_AA73EEB78EAE3863');
        $this->addSql('ALTER TABLE intervention_service DROP FOREIGN KEY FK_AA73EEB7ED5CA9E6');
        $this->addSql('ALTER TABLE ligne_facture DROP FOREIGN KEY FK_611F5A297F2DEE08');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F9AC0396');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE password_reset_token DROP FOREIGN KEY FK_6B7BA4B6A76ED395');
        $this->addSql('ALTER TABLE photo DROP FOREIGN KEY FK_14B784188805AB2F');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A19EB6921');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A4A4A3511');
        $this->addSql('ALTER TABLE rendez_vous DROP FOREIGN KEY FK_65E8AA0A20A01F78');
        $this->addSql('ALTER TABLE vehicule DROP FOREIGN KEY FK_292FFF1D76C50E4A');
        $this->addSql('DROP TABLE annonce');
        $this->addSql('DROP TABLE conversation');
        $this->addSql('DROP TABLE email_verification_token');
        $this->addSql('DROP TABLE facture');
        $this->addSql('DROP TABLE intervention');
        $this->addSql('DROP TABLE intervention_service');
        $this->addSql('DROP TABLE ligne_facture');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE password_reset_token');
        $this->addSql('DROP TABLE photo');
        $this->addSql('DROP TABLE rendez_vous');
        $this->addSql('DROP TABLE service');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE vehicule');
    }
}
