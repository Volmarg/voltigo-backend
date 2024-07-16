<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230519151823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result DROP INDEX FK_6156F0C053350EC2, ADD UNIQUE INDEX UNIQ_6156F0C053350EC2 (returned_points_history_id)');
        $this->addSql('ALTER TABLE job_search_result ADD user_point_history_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE job_search_result ADD CONSTRAINT FK_6156F0C0BF65E525 FOREIGN KEY (user_point_history_id) REFERENCES user_point_history (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result DROP INDEX UNIQ_6156F0C053350EC2, ADD INDEX FK_6156F0C053350EC2 (returned_points_history_id)');
        $this->addSql('ALTER TABLE job_search_result DROP FOREIGN KEY FK_6156F0C0BF65E525');
        $this->addSql('DROP INDEX UNIQ_6156F0C0BF65E525 ON job_search_result');
        $this->addSql('ALTER TABLE job_search_result DROP user_point_history_id');
    }
}
