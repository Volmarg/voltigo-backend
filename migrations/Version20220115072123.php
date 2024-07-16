<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220115072123 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email ADD tool_name VARCHAR(50) DEFAULT NULL, ADD error LONGTEXT DEFAULT NULL, ADD external_id INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C0600CA79DA47C3 ON email_template (email_template_name)'); # STAYS
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email DROP tool_name, DROP external_id');
        $this->addSql('DROP INDEX UNIQ_9C0600CA79DA47C3 ON email_template');
    }
}
