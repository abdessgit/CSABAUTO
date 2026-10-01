<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table message_contact (compatible MySQL et PostgreSQL)
 */
final class Version20261001191805 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table message_contact pour le formulaire de contact';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE message_contact (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(30) DEFAULT NULL, sujet VARCHAR(150) DEFAULT NULL, message LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, reponse LONGTEXT DEFAULT NULL, date_reponse DATETIME DEFAULT NULL, ip_adresse VARCHAR(45) DEFAULT NULL, repond_par_id INT DEFAULT NULL, INDEX idx_message_contact_statut (statut), INDEX idx_message_contact_date_envoi (date_envoi), INDEX IDX_DCEADC345938D37B (repond_par_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE message_contact ADD CONSTRAINT FK_DCEADC345938D37B FOREIGN KEY (repond_par_id) REFERENCES utilisateur (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE message_contact DROP FOREIGN KEY FK_DCEADC345938D37B');
        $this->addSql('DROP TABLE message_contact');
    }
}
