<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220730105751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE amqp_storage (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, related_storage_entry_id INT DEFAULT NULL, target_class VARCHAR(300) DEFAULT NULL, message VARCHAR(900) NOT NULL, unique_id VARCHAR(255) NOT NULL, expect_response TINYINT(1) NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE INDEX UNIQ_FF5A3564E3C68343 (unique_id), INDEX IDX_FF5A3564A76ED395 (user_id), INDEX IDX_FF5A35645BC2853C (related_storage_entry_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE amqp_storage ADD CONSTRAINT FK_FF5A3564A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE amqp_storage ADD CONSTRAINT FK_FF5A35645BC2853C FOREIGN KEY (related_storage_entry_id) REFERENCES amqp_storage (id)');
        $this->addSql('ALTER TABLE `order` CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE payment_method CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE payment_method_type CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE product CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE product_configuration CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE product_configuration_snapshot CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE product_snapshot CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user_data_snapshot CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amqp_storage DROP FOREIGN KEY FK_FF5A35645BC2853C');
        $this->addSql('DROP TABLE amqp_storage');
        $this->addSql('DROP INDEX UNIQ_9C0600CA79DA47C3 ON email_template');
        $this->addSql('ALTER TABLE `order` CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE payment_method CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE payment_method_type CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product_configuration CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product_configuration_snapshot CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product_snapshot CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_data_snapshot CHANGE modified modified DATETIME NOT NULL');
    }
}
