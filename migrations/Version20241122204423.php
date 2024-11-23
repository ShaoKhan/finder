<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122204423 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Anpassen der Werte für die UTM Werte';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image CHANGE utm_x utm_x NUMERIC(20, 8) DEFAULT NULL, CHANGE utm_y utm_y NUMERIC(20, 8) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image CHANGE utm_x utm_x NUMERIC(10, 8) DEFAULT NULL, CHANGE utm_y utm_y NUMERIC(11, 8) DEFAULT NULL');
    }
}
