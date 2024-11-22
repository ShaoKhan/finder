<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241122223304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'new field for image is public or not';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image ADD is_public TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image DROP is_public');
    }
}
