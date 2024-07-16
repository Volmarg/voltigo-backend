<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230519143238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result ADD returned_points_history_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_search_result ADD CONSTRAINT FK_6156F0C053350EC2 FOREIGN KEY (returned_points_history_id) REFERENCES user_point_history (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result DROP FOREIGN KEY FK_6156F0C053350EC2');
        $this->addSql('DROP INDEX UNIQ_6156F0C053350EC2 ON job_search_result');
        $this->addSql('ALTER TABLE job_search_result DROP returned_points_history_id');
    }
}
