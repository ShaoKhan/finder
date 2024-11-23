<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122203800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'tabelle für die Fundbilder';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE founds_image (id INT AUTO_INCREMENT NOT NULL, file_path VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, note LONGTEXT NOT NULL, username VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, utm_x NUMERIC(10, 8) DEFAULT NULL, utm_y NUMERIC(11, 8) DEFAULT NULL, parcel VARCHAR(255) DEFAULT NULL, district VARCHAR(255) DEFAULT NULL, county VARCHAR(255) DEFAULT NULL, state VARCHAR(255) DEFAULT NULL, nearest_street VARCHAR(255) DEFAULT NULL, nearest_town VARCHAR(255) DEFAULT NULL, distance_to_church_or_center NUMERIC(6, 2) DEFAULT NULL, church_or_center_name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE founds_image');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
