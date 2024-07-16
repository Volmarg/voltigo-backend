<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220221153426 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE job_search_result CHANGE target_area target_areas LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE `order` CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE payment_method CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE payment_method_type CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE product CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE product_configuration CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE product_configuration_snapshot CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE product_snapshot CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE user_data_snapshot CHANGE modified modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_9C0600CA79DA47C3 ON email_template');
        $this->addSql('ALTER TABLE job_search_result CHANGE target_areas target_area LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:array)\'');
        $this->addSql('ALTER TABLE `order` CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE payment_method CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE payment_method_type CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product_configuration CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product_configuration_snapshot CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE product_snapshot CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE modified modified DATETIME NOT NULL');
        $this->addSql('ALTER TABLE user_data_snapshot CHANGE modified modified DATETIME NOT NULL');
    }
}
