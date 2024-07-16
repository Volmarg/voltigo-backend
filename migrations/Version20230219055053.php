<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230219055053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment_method DROP FOREIGN KEY FK_7B61A1F62476A5D8');
        $this->addSql('CREATE TABLE address_snapshot (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, zip VARCHAR(50) DEFAULT NULL, street VARCHAR(150) DEFAULT NULL, city VARCHAR(150) DEFAULT NULL, home_number VARCHAR(100) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, created DATETIME NOT NULL, UNIQUE INDEX UNIQ_D2C2EBB4A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE point_product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, created DATETIME NOT NULL, deleted TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, amount INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE point_product_snapshot (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, created DATETIME NOT NULL, amount INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE address_snapshot ADD CONSTRAINT FK_D2C2EBB4A76ED395 FOREIGN KEY (user_id) REFERENCES user_data_snapshot (id)');
        $this->addSql('DROP TABLE bill');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE payment_method_type');
        $this->addSql('DROP TABLE product_configuration');
        $this->addSql('DROP TABLE product_configuration_snapshot');
        $this->addSql('ALTER TABLE `order` ADD user_snapshot_id INT NOT NULL, ADD status VARCHAR(255) NOT NULL, ADD activated TINYINT(1) NOT NULL, ADD mailed TINYINT(1) NOT NULL, CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398D222F0CB FOREIGN KEY (user_snapshot_id) REFERENCES user_data_snapshot (id)');
        $this->addSql('ALTER TABLE product CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE product_snapshot CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user_data_snapshot ADD firstname VARCHAR(100) DEFAULT NULL, ADD lastname VARCHAR(100) DEFAULT NULL, CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
