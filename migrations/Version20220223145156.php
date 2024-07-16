<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220223145156 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_search_result_job_offer_information (job_search_result_id INT NOT NULL, job_offer_information_id INT NOT NULL, INDEX IDX_88FD6839615A466D (job_search_result_id), INDEX IDX_88FD683928E87F9E (job_offer_information_id), PRIMARY KEY(job_search_result_id, job_offer_information_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_search_result_job_offer_information ADD CONSTRAINT FK_88FD6839615A466D FOREIGN KEY (job_search_result_id) REFERENCES job_search_result (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_search_result_job_offer_information ADD CONSTRAINT FK_88FD683928E87F9E FOREIGN KEY (job_offer_information_id) REFERENCES job_offer_information (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job_offer_information DROP FOREIGN KEY FK_AC5D6478615A466D');
        $this->addSql('DROP INDEX IDX_AC5D6478615A466D ON job_offer_information');
        $this->addSql('ALTER TABLE job_offer_information DROP job_search_result_id');
    }

    public function down(Schema $schema): void
    {
    }
}
