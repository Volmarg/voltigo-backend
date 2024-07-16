<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230223142540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993984584665A');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398D716A741');
        $this->addSql('DROP INDEX IDX_F52993984584665A ON `order`');
        $this->addSql('DROP INDEX UNIQ_F5299398D716A741 ON `order`');
        $this->addSql('ALTER TABLE `order` DROP product_id, DROP product_snapshot_id');
        $this->addSql('ALTER TABLE product_snapshot ADD product_id INT DEFAULT NULL, ADD order_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product_snapshot ADD CONSTRAINT FK_E7FD7FA24584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_snapshot ADD CONSTRAINT FK_E7FD7FA28D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('CREATE INDEX IDX_E7FD7FA24584665A ON product_snapshot (product_id)');
        $this->addSql('CREATE INDEX IDX_E7FD7FA28D9F6D38 ON product_snapshot (order_id)');
    }

    public function down(Schema $schema): void
    {
    }
}
