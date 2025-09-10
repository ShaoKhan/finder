<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250120000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Begehung entity and GPS tracking functionality';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE begehung (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, uuid VARCHAR(255) NOT NULL UNIQUE, start_latitude NUMERIC(10, 8) DEFAULT NULL, start_longitude NUMERIC(11, 8) DEFAULT NULL, end_latitude NUMERIC(10, 8) DEFAULT NULL, end_longitude NUMERIC(11, 8) DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INT DEFAULT NULL, polygon_data LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_BEGEHUING_USER_ID (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE begehung ADD CONSTRAINT FK_BEGEHUING_USER_ID FOREIGN KEY (user_id) REFERENCES user (id)');
        
        // Füge neue Spalten zur founds_image Tabelle hinzu (nur wenn sie nicht existieren)
        $this->addSql('ALTER TABLE founds_image ADD begehung_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE founds_image ADD track_index INT DEFAULT NULL');
        $this->addSql('ALTER TABLE founds_image ADD CONSTRAINT FK_FOUNDS_IMAGE_BEGEHUING_ID FOREIGN KEY (begehung_id) REFERENCES begehung (id)');
        $this->addSql('CREATE INDEX IDX_FOUNDS_IMAGE_BEGEHUING_ID ON founds_image (begehung_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE founds_image DROP FOREIGN KEY FK_FOUNDS_IMAGE_BEGEHUING_ID');
        $this->addSql('DROP INDEX IDX_FOUNDS_IMAGE_BEGEHUING_ID ON founds_image');
        $this->addSql('ALTER TABLE founds_image DROP begehung_id, DROP track_index');
        $this->addSql('ALTER TABLE begehung DROP FOREIGN KEY FK_BEGEHUING_USER_ID');
        $this->addSql('DROP TABLE begehung');
    }
}
