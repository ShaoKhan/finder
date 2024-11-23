<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122213532 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'extend fields for camera and location';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image ADD camera_model VARCHAR(255) DEFAULT NULL, ADD exposure_time VARCHAR(255) DEFAULT NULL, ADD f_number VARCHAR(255) DEFAULT NULL, ADD iso INT DEFAULT NULL, ADD date_time DATETIME DEFAULT NULL, ADD latitude NUMERIC(10, 8) DEFAULT NULL, ADD longitude NUMERIC(11, 8) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image DROP camera_model, DROP exposure_time, DROP f_number, DROP iso, DROP date_time, DROP latitude, DROP longitude');
    }
}
