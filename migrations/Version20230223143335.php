<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230223143335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE point_product_snapshot DROP FOREIGN KEY FK_1698404BF396750');
        $this->addSql('CREATE TABLE order_point_product_snapshot (id INT NOT NULL, amount INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_product_snapshot (id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, order_id INT DEFAULT NULL, quantity INT NOT NULL, name VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, price_with_tax DOUBLE PRECISION NOT NULL, tax_percentage DOUBLE PRECISION NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, created DATETIME NOT NULL, discr VARCHAR(255) NOT NULL, INDEX IDX_85EB6BBE4584665A (product_id), INDEX IDX_85EB6BBE8D9F6D38 (order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE order_point_product_snapshot ADD CONSTRAINT FK_DE3C60ABBF396750 FOREIGN KEY (id) REFERENCES order_product_snapshot (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_product_snapshot ADD CONSTRAINT FK_85EB6BBE4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE order_product_snapshot ADD CONSTRAINT FK_85EB6BBE8D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('DROP TABLE point_product_snapshot');
        $this->addSql('DROP TABLE product_snapshot');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
