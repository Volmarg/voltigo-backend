<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221002035806 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE banned_ip_storage (id INT AUTO_INCREMENT NOT NULL, ip VARCHAR(255) NOT NULL, issued_by LONGTEXT NOT NULL, lifetime TINYINT(1) NOT NULL, reason LONGTEXT NOT NULL, valid_till DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE banned_user_storage (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, issued_by LONGTEXT NOT NULL, lifetime TINYINT(1) NOT NULL, reason LONGTEXT NOT NULL, valid_till DATETIME DEFAULT NULL, INDEX IDX_5B3B25E8A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE banned_user_storage ADD CONSTRAINT FK_5B3B25E8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');

        $this->addSql('ALTER TABLE banned_ip_storage ADD modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, ADD created DATETIME NOT NULL');
        $this->addSql('ALTER TABLE banned_user_storage ADD modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, ADD created DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE banned_ip_storage');
        $this->addSql('DROP TABLE banned_user_storage');
    }
}
