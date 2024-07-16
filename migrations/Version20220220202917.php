<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220220202917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE job_search_result (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, keywords LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', target_area LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', status VARCHAR(50) NOT NULL, finished DATETIME DEFAULT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX IDX_6156F0C0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE job_search_result ADD CONSTRAINT FK_6156 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE job_offer_information ADD job_search_result_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_offer_information ADD CONSTRAINT FK_AC5D6478615A466D FOREIGN KEY (job_search_result_id) REFERENCES job_search_result (id)');
        $this->addSql('CREATE INDEX IDX_AC5D6478615A466D ON job_offer_information (job_search_result_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_offer_information DROP FOREIGN KEY FK_6156');
        $this->addSql('DROP TABLE job_search_result');
        $this->addSql('DROP INDEX IDX_AC5D6478615A466D ON job_offer_information');
        $this->addSql('ALTER TABLE job_offer_information DROP job_search_result_id');
    }
}
