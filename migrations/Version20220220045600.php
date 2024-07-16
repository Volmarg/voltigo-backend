<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220220045600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE job_application_job_offer_information');
        $this->addSql('ALTER TABLE job_application ADD job_offer_id INT NOT NULL');
        $this->addSql('ALTER TABLE job_application ADD CONSTRAINT FK_C737C6883481D195 FOREIGN KEY (job_offer_id) REFERENCES job_offer_information (id)');
        $this->addSql('CREATE INDEX IDX_C737C6883481D195 ON job_application (job_offer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_application_job_offer_information (job_application_id INT NOT NULL, job_offer_information_id INT NOT NULL, INDEX IDX_BACB310028E87F9E (job_offer_information_id), INDEX IDX_BACB3100AC7A5A08 (job_application_id), PRIMARY KEY(job_application_id, job_offer_information_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE job_application_job_offer_information ADD CONSTRAINT FK_BACB310028E87F9E FOREIGN KEY (job_offer_information_id) REFERENCES job_offer_information (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application_job_offer_information ADD CONSTRAINT FK_BACB3100AC7A5A08 FOREIGN KEY (job_application_id) REFERENCES job_application (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application DROP FOREIGN KEY FK_C737C6883481D195');
        $this->addSql('DROP INDEX IDX_C737C6883481D195 ON job_application');
        $this->addSql('ALTER TABLE job_application DROP job_offer_id');
    }
}
