<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230424134427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE regulation_data (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, hash VARCHAR(255) NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, created DATETIME NOT NULL, UNIQUE INDEX UNIQ_C1EAC7C7D1B862B8 (hash), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_regulation (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, regulation_data_id INT DEFAULT NULL, identifier VARCHAR(255) DEFAULT NULL, accepted TINYINT(1) NOT NULL, accept_date DATETIME DEFAULT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, created DATETIME NOT NULL, INDEX IDX_F48B0475A76ED395 (user_id), INDEX IDX_F48B047536C50FDB (regulation_data_id), UNIQUE INDEX UNIQ_F48B0475A76ED395772E836AB23DB7B836C50FDB (user_id, identifier, created, regulation_data_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_regulation ADD CONSTRAINT FK_F48B0475A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_regulation ADD CONSTRAINT FK_F48B047536C50FDB FOREIGN KEY (regulation_data_id) REFERENCES regulation_data (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_regulation DROP FOREIGN KEY FK_F48B047536C50FDB');
        $this->addSql('DROP TABLE regulation_data');
        $this->addSql('DROP TABLE user_regulation');
    }
}
