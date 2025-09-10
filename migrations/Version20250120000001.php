<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250120000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add GPS tracking columns to founds_image table';
    }

    public function up(Schema $schema): void
    {
        // Prüfe ob die Spalten bereits existieren
        $connection = $this->connection;
        
        // Prüfe begehung_id Spalte
        $columns = $connection->fetchAllAssociative("SHOW COLUMNS FROM founds_image LIKE 'begehung_id'");
        if (empty($columns)) {
            $this->addSql('ALTER TABLE founds_image ADD begehung_id INT DEFAULT NULL');
        }
        
        // Prüfe track_index Spalte
        $columns = $connection->fetchAllAssociative("SHOW COLUMNS FROM founds_image LIKE 'track_index'");
        if (empty($columns)) {
            $this->addSql('ALTER TABLE founds_image ADD track_index INT DEFAULT NULL');
        }
        
        // Prüfe ob die Begehung-Tabelle existiert
        $tables = $connection->fetchAllAssociative("SHOW TABLES LIKE 'begehung'");
        if (empty($tables)) {
            $this->addSql('CREATE TABLE begehung (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, uuid VARCHAR(255) NOT NULL UNIQUE, start_latitude NUMERIC(10, 8) DEFAULT NULL, start_longitude NUMERIC(11, 8) DEFAULT NULL, end_latitude NUMERIC(10, 8) DEFAULT NULL, end_longitude NUMERIC(11, 8) DEFAULT NULL, start_time DATETIME NOT NULL, end_time DATETIME DEFAULT NULL, duration INT DEFAULT NULL, polygon_data LONGTEXT DEFAULT NULL, is_active TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_BEGEHUING_USER_ID (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE begehung ADD CONSTRAINT FK_BEGEHUING_USER_ID FOREIGN KEY (user_id) REFERENCES user (id)');
        }
        
        // Prüfe ob der Foreign Key bereits existiert
        $constraints = $connection->fetchAllAssociative("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'founds_image' AND CONSTRAINT_NAME = 'FK_FOUNDS_IMAGE_BEGEHUING_ID'");
        if (empty($constraints)) {
            $this->addSql('ALTER TABLE founds_image ADD CONSTRAINT FK_FOUNDS_IMAGE_BEGEHUING_ID FOREIGN KEY (begehung_id) REFERENCES begehung (id)');
        }
        
        // Prüfe ob der Index bereits existiert
        $indexes = $connection->fetchAllAssociative("SHOW INDEX FROM founds_image WHERE Key_name = 'IDX_FOUNDS_IMAGE_BEGEHUING_ID'");
        if (empty($indexes)) {
            $this->addSql('CREATE INDEX IDX_FOUNDS_IMAGE_BEGEHUING_ID ON founds_image (begehung_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image DROP FOREIGN KEY FK_FOUNDS_IMAGE_BEGEHUING_ID');
        $this->addSql('DROP INDEX IDX_FOUNDS_IMAGE_BEGEHUING_ID ON founds_image');
        $this->addSql('ALTER TABLE founds_image DROP begehung_id, DROP track_index');
        $this->addSql('ALTER TABLE begehung DROP FOREIGN KEY FK_BEGEHUING_USER_ID');
        $this->addSql('DROP TABLE begehung');
    }
}
