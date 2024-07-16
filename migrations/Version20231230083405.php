<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231230083405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add payment process data';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE `payment_process_data` (id INT AUTO_INCREMENT NOT NULL, target_currency_calculated_unit_price DOUBLE PRECISION NOT NULL, target_currency_calculated_unit_price_with_tax DOUBLE PRECISION NOT NULL, payment_tool_data JSON NOT NULL, payment_tool VARCHAR(255) NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE `order` ADD payment_process_data_id INT NOT NULL');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993987805974A FOREIGN KEY (payment_process_data_id) REFERENCES `payment_process_data` (id)');
        $this->addSql('CREATE INDEX IDX_F52993987805974A ON `order` (payment_process_data_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993987805974A');
        $this->addSql('DROP TABLE `payment_process_data`');
        $this->addSql('DROP INDEX UNIQ_F52993987805974A ON `order`');
        $this->addSql('ALTER TABLE `order` DROP payment_process_data_id');
    }
}
