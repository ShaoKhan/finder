<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241127215700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change uuid from string to uuid';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user CHANGE uuid uuid BINARY(128) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D17F50A6 ON user (uuid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D649D17F50A6 ON user');
        $this->addSql('ALTER TABLE user CHANGE uuid uuid VARCHAR(255) NOT NULL');
    }
}