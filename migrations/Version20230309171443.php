<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230309171443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE point_shop_product (id INT AUTO_INCREMENT NOT NULL, internal_identifier varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, cost decimal(10,0) NOT NULL, name VARCHAR(255) NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, created DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_point_history (id INT AUTO_INCREMENT NOT NULL, related_order_id INT DEFAULT NULL, user_id INT NOT NULL, amount_before INT NOT NULL, amount_now INT NOT NULL, type VARCHAR(255) NOT NULL, information LONGTEXT NOT NULL, extra_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, internal_data longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, point_shop_product_snapshot LONGTEXT DEFAULT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, created DATETIME NOT NULL, INDEX IDX_7EE024D72B1C2395 (related_order_id), INDEX IDX_7EE024D7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_point_history ADD CONSTRAINT FK_7EE024D72B1C2395 FOREIGN KEY (related_order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE user_point_history ADD CONSTRAINT FK_7EE024D7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE point_shop_product');
        $this->addSql('DROP TABLE user_point_history');
    }
}
