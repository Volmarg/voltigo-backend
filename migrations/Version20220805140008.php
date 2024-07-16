<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220805140008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
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
