<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261001131549 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs TVA, HT, TTC, date_prestation, date_echeance sur Facture et taux_tva sur LigneFacture';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture ADD date_prestation DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE facture ADD taux_tva NUMERIC(5, 2) DEFAULT \'20.00\' NOT NULL');
        $this->addSql('ALTER TABLE facture ADD montant_ht NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE facture ADD montant_tva NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE facture ADD montant_ttc NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE facture ADD date_echeance DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_facture ADD taux_tva NUMERIC(5, 2) DEFAULT \'20.00\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture DROP date_prestation');
        $this->addSql('ALTER TABLE facture DROP taux_tva');
        $this->addSql('ALTER TABLE facture DROP montant_ht');
        $this->addSql('ALTER TABLE facture DROP montant_tva');
        $this->addSql('ALTER TABLE facture DROP montant_ttc');
        $this->addSql('ALTER TABLE facture DROP date_echeance');
        $this->addSql('ALTER TABLE ligne_facture DROP taux_tva');
    }
}
