<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les attributs véhicule, photos JSON et dates sur l'entité Annonce.
 */
final class Version20260928134040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs complets du véhicule sur annonce, photos JSON et statut PUBLIEE/BROUILLON/VENDUE';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE annonce ADD marque VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD modele VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD annee INT DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD kilometrage INT DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD carburant VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD boite VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD puissance INT DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD couleur VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD nb_portes INT DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD nb_places INT DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD photos JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE annonce ADD date_creation TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE annonce ALTER description DROP NOT NULL');
        $this->addSql('ALTER TABLE annonce ALTER date_publication DROP NOT NULL');
        $this->addSql('ALTER TABLE annonce ALTER vehicule_id DROP NOT NULL');

        // Mettre à jour les annonces existantes depuis les véhicules liés
        $this->addSql("UPDATE annonce a SET 
            marque = COALESCE(v.marque, 'Inconnue'),
            modele = COALESCE(v.modele, 'Inconnu'),
            annee = COALESCE(v.annee, 2020),
            kilometrage = COALESCE(v.kilometrage, 0),
            couleur = v.couleur,
            statut = CASE WHEN a.statut = 'EN_VENTE' THEN 'PUBLIEE' WHEN a.statut = 'VENDU' THEN 'VENDUE' ELSE a.statut END
            FROM vehicule v WHERE a.vehicule_id = v.id");

        // Remplir les photos pour les annonces existantes depuis la table photo
        $this->addSql("UPDATE annonce a SET photos = sub.urls FROM (
            SELECT annonce_id, json_agg(url ORDER BY ordre_affichage ASC) as urls
            FROM photo GROUP BY annonce_id
        ) sub WHERE a.id = sub.annonce_id");

        // Valeurs par défaut de secours si nécessaire
        $this->addSql("UPDATE annonce SET marque = 'Inconnue' WHERE marque IS NULL");
        $this->addSql("UPDATE annonce SET modele = 'Inconnu' WHERE modele IS NULL");
        $this->addSql("UPDATE annonce SET annee = 2020 WHERE annee IS NULL");
        $this->addSql("UPDATE annonce SET kilometrage = 0 WHERE kilometrage IS NULL");
        $this->addSql("UPDATE annonce SET date_creation = COALESCE(date_publication, CURRENT_TIMESTAMP) WHERE date_creation IS NULL");

        // Application des contraintes NOT NULL
        $this->addSql('ALTER TABLE annonce ALTER COLUMN marque SET NOT NULL');
        $this->addSql('ALTER TABLE annonce ALTER COLUMN modele SET NOT NULL');
        $this->addSql('ALTER TABLE annonce ALTER COLUMN annee SET NOT NULL');
        $this->addSql('ALTER TABLE annonce ALTER COLUMN kilometrage SET NOT NULL');
        $this->addSql('ALTER TABLE annonce ALTER COLUMN date_creation SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE annonce DROP marque');
        $this->addSql('ALTER TABLE annonce DROP modele');
        $this->addSql('ALTER TABLE annonce DROP annee');
        $this->addSql('ALTER TABLE annonce DROP kilometrage');
        $this->addSql('ALTER TABLE annonce DROP carburant');
        $this->addSql('ALTER TABLE annonce DROP boite');
        $this->addSql('ALTER TABLE annonce DROP puissance');
        $this->addSql('ALTER TABLE annonce DROP couleur');
        $this->addSql('ALTER TABLE annonce DROP nb_portes');
        $this->addSql('ALTER TABLE annonce DROP nb_places');
        $this->addSql('ALTER TABLE annonce DROP photos');
        $this->addSql('ALTER TABLE annonce DROP date_creation');
        $this->addSql('ALTER TABLE annonce ALTER description SET NOT NULL');
        $this->addSql('ALTER TABLE annonce ALTER date_publication SET NOT NULL');
        $this->addSql('ALTER TABLE annonce ALTER vehicule_id SET NOT NULL');
    }
}
