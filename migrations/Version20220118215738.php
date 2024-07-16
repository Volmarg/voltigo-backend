<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220118215738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email ADD status VARCHAR(50) NOT NULL, ADD anonymized TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE job_application ADD email_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C688A832C1C9 FOREIGN KEY (email_id) REFERENCES email (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C737C688A832C1C9 ON job_application (email_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email DROP status, DROP anonymized');
        $this->addSql('DROP INDEX UNIQ_9C0600CA79DA47C3 ON email_template');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C688A832C1C9');
        $this->addSql('DROP INDEX UNIQ_C737C688A832C1C9 ON job_application');
        $this->addSql('ALTER TABLE job_application DROP email_id');
    }
}
