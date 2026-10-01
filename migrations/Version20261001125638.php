<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter le champ ordre sur ligne_facture
 */
final class Version20261001125638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ ordre sur ligne_facture';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ligne_facture ADD ordre INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ligne_facture DROP COLUMN ordre');
    }
}
