<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122225109 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'name and note can be NULL';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image CHANGE name name VARCHAR(255) DEFAULT NULL, CHANGE note note LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image CHANGE note note LONGTEXT NOT NULL, CHANGE name name VARCHAR(255) NOT NULL');
    }
}
