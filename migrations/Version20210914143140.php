<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210914143140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_storage ADD modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE page_tracking_storage ADD modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP');

        $this->addSql("
            INSERT INTO account_type(id, name, days_duration, created, modified, deleted, active)
            VALUES(
                   null,
                   'FREE',
                   null,
                   CURRENT_TIMESTAMP,
                   CURRENT_TIMESTAMP,
                   0,
                   1
            )
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_storage DROP modified');
        $this->addSql('ALTER TABLE page_tracking_storage DROP modified');
    }
}
