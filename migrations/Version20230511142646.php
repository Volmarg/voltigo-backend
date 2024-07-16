<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230511142646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amqp_storage DROP FOREIGN KEY FK_FF5A35645BC2853C');
        $this->addSql('ALTER TABLE amqp_storage ADD CONSTRAINT FK_FF5A35645BC2853C FOREIGN KEY (related_storage_entry_id) REFERENCES amqp_storage (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE amqp_storage DROP FOREIGN KEY FK_FF5A35645BC2853C');
        $this->addSql('ALTER TABLE amqp_storage ADD CONSTRAINT FK_FF5A35645BC2853C FOREIGN KEY (related_storage_entry_id) REFERENCES amqp_storage (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
