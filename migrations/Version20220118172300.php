<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220118172300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_application (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(100) NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_application_job_offer_information (job_application_id INT NOT NULL, job_offer_information_id INT NOT NULL, INDEX IDX_BACB3100AC7A5A08 (job_application_id), INDEX IDX_BACB310028E87F9E (job_offer_information_id), PRIMARY KEY(job_application_id, job_offer_information_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE job_offer_information (id INT AUTO_INCREMENT NOT NULL, title LONGTEXT NOT NULL, company_name VARCHAR(255) NOT NULL, original_url LONGTEXT NOT NULL, external_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, deleted TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_application_job_offer_information ADD CONSTRAINT FK_BACB3100AC7A5A08 FOREIGN KEY (job_application_id) REFERENCES job_application (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_application_job_offer_information ADD CONSTRAINT FK_BACB310028E87F9E FOREIGN KEY (job_offer_information_id) REFERENCES job_offer_information (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_application_job_offer_information DROP FOREIGN KEY FK_BACB3100AC7A5A08');
        $this->addSql('ALTER TABLE job_application_job_offer_information DROP FOREIGN KEY FK_BACB310028E87F9E');
        $this->addSql('DROP TABLE job_application');
        $this->addSql('DROP TABLE job_application_job_offer_information');
        $this->addSql('DROP TABLE job_offer_information');
    }
}
