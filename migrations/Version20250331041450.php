<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250331041450 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE demande_sponsoring (id INT AUTO_INCREMENT NOT NULL, sponsor INT NOT NULL, event_id INT NOT NULL, statut VARCHAR(255) DEFAULT NULL, justification LONGTEXT DEFAULT NULL, INDEX IDX_1295AFAF818CC9D4 (sponsor), INDEX IDX_1295AFAF71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, type VARCHAR(100) NOT NULL, nombreInvite INT NOT NULL, dateDebut DATETIME NOT NULL, dateFin DATETIME NOT NULL, description LONGTEXT DEFAULT NULL, lieuEvenement VARCHAR(255) DEFAULT NULL, budgetPrevu DOUBLE PRECISION DEFAULT NULL, activities LONGTEXT DEFAULT NULL, imagePath VARCHAR(255) DEFAULT NULL, validated VARCHAR(255) DEFAULT NULL, INDEX IDX_B26681EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, motDePasse VARCHAR(255) NOT NULL, telephone VARCHAR(20) NOT NULL, role VARCHAR(20) NOT NULL, compteValide TINYINT(1) DEFAULT 0 NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE demande_sponsoring ADD CONSTRAINT FK_1295AFAF818CC9D4 FOREIGN KEY (sponsor) REFERENCES user (id)');
        $this->addSql('ALTER TABLE demande_sponsoring ADD CONSTRAINT FK_1295AFAF71F7E88B FOREIGN KEY (event_id) REFERENCES evenement (id)');
        $this->addSql('ALTER TABLE evenement ADD CONSTRAINT FK_B26681EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande_sponsoring DROP FOREIGN KEY FK_1295AFAF818CC9D4');
        $this->addSql('ALTER TABLE demande_sponsoring DROP FOREIGN KEY FK_1295AFAF71F7E88B');
        $this->addSql('ALTER TABLE evenement DROP FOREIGN KEY FK_B26681EA76ED395');
        $this->addSql('DROP TABLE demande_sponsoring');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
