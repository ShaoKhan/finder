<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241128111727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'add relation to UserEntity';
    }

    public function up(Schema $schema): void
    {

        $this->addSql('ALTER TABLE founds_image ADD user_id INT DEFAULT NULL, ADD user_uuid VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE founds_image ADD CONSTRAINT FK_8CB15404A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8CB15404A76ED395 ON founds_image (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE founds_image DROP FOREIGN KEY FK_8CB15404A76ED395');
        $this->addSql('DROP INDEX IDX_8CB15404A76ED395 ON founds_image');
        $this->addSql('ALTER TABLE founds_image DROP user_id, DROP user_uuid');
    }
}
