<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210912133153 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE account (id INT AUTO_INCREMENT NOT NULL, type_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, deleted TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, INDEX IDX_7D3656A4C54C8C93 (type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE account_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, days_duration INT DEFAULT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP, deleted TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE api_storage (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, request_content LONGTEXT DEFAULT NULL, request_uri LONGTEXT NOT NULL, called_api_name VARCHAR(50) NOT NULL, call_direction VARCHAR(50) NOT NULL, status VARCHAR(50) NOT NULL, method VARCHAR(50) NOT NULL, headers JSON DEFAULT NULL, query_parameters JSON DEFAULT NULL, request_parameters JSON DEFAULT NULL, created DATETIME NOT NULL, INDEX IDX_B9446F53A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bill (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, cost_id INT NOT NULL, paid_amount DOUBLE PRECISION NOT NULL, is_fully_paid TINYINT(1) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX IDX_7A2119E3A76ED395 (user_id), UNIQUE INDEX UNIQ_7A2119E31DBF857F (cost_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cost (id INT AUTO_INCREMENT NOT NULL, related_order_id INT NOT NULL, total_without_tax DOUBLE PRECISION NOT NULL, total_with_tax DOUBLE PRECISION NOT NULL, used_tax_value INT NOT NULL, currency_code varchar(55) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, UNIQUE INDEX UNIQ_182694FC2B1C2395 (related_order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE csrf_token_storage (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, token_id LONGTEXT NOT NULL, generated_token LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE frontend_error_storage (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, data JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, user_data_snapshot_id INT NOT NULL, product_snapshot_id INT NOT NULL, price DOUBLE PRECISION NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, INDEX IDX_F5299398A76ED395 (user_id), INDEX IDX_F52993984584665A (product_id), UNIQUE INDEX UNIQ_F5299398F9EC24C9 (user_data_snapshot_id), UNIQUE INDEX UNIQ_F5299398D716A741 (product_snapshot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE page_tracking_storage (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, ip VARCHAR(255) NOT NULL, request_content LONGTEXT DEFAULT NULL, request_uri LONGTEXT NOT NULL, method VARCHAR(50) NOT NULL, headers JSON DEFAULT NULL, query_parameters JSON DEFAULT NULL, request_parameters JSON DEFAULT NULL, created DATETIME NOT NULL, INDEX IDX_4ABFD2D0A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_method (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, payment_method_type_id INT NOT NULL, method_identifier VARCHAR(255) NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, deleted TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_7B61A1F6A53DE102 (method_identifier), INDEX IDX_7B61A1F6A76ED395 (user_id), INDEX IDX_7B61A1F62476A5D8 (payment_method_type_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE payment_method_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, description LONGTEXT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, deleted TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, base_currency_code varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL, name VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, deleted TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_configuration (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, taxable TINYINT(1) NOT NULL, deleted TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_7F0FB9254584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_configuration_snapshot (id INT AUTO_INCREMENT NOT NULL, product_snapshot_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, taxable TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_F6E95C8D716A741 (product_snapshot_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_snapshot (id INT AUTO_INCREMENT NOT NULL, tax_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, name VARCHAR(100) NOT NULL, price DOUBLE PRECISION NOT NULL, INDEX IDX_E7FD7FA2B2A824D8 (tax_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, account_id INT NOT NULL, email VARCHAR(180) NOT NULL, username TINYTEXT NOT NULL, roles JSON NOT NULL, password LONGTEXT NOT NULL, last_activity DATETIME DEFAULT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, deleted TINYINT(1) NOT NULL, active TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D6499B6B5FBA (account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_data_snapshot (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, created DATETIME NOT NULL, modified DATETIME NOT NULL ON UPDATE CURRENT_TIMESTAMP, account_type_name VARCHAR(50) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, roles JSON NOT NULL, INDEX IDX_81D71B9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE account ADD CONSTRAINT FK_7D3656A4C54C8C93 FOREIGN KEY (type_id) REFERENCES account_type (id)');
        $this->addSql('ALTER TABLE api_storage ADD CONSTRAINT FK_B9446F53A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE bill ADD CONSTRAINT FK_7A2119E3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE bill ADD CONSTRAINT FK_7A2119E31DBF857F FOREIGN KEY (cost_id) REFERENCES cost (id)');
        $this->addSql('ALTER TABLE cost ADD CONSTRAINT FK_182694FC2B1C2395 FOREIGN KEY (related_order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993984584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398F9EC24C9 FOREIGN KEY (user_data_snapshot_id) REFERENCES user_data_snapshot (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398D716A741 FOREIGN KEY (product_snapshot_id) REFERENCES product_snapshot (id)');
        $this->addSql('ALTER TABLE page_tracking_storage ADD CONSTRAINT FK_4ABFD2D0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payment_method ADD CONSTRAINT FK_7B61A1F6A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE payment_method ADD CONSTRAINT FK_7B61A1F62476A5D8 FOREIGN KEY (payment_method_type_id) REFERENCES payment_method_type (id)');
        $this->addSql('ALTER TABLE product_configuration ADD CONSTRAINT FK_7F0FB9254584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_configuration_snapshot ADD CONSTRAINT FK_F6E95C8D716A741 FOREIGN KEY (product_snapshot_id) REFERENCES product_snapshot (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6499B6B5FBA FOREIGN KEY (account_id) REFERENCES account (id)');
        $this->addSql('ALTER TABLE user_data_snapshot ADD CONSTRAINT FK_81D71B9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6499B6B5FBA');
        $this->addSql('ALTER TABLE account DROP FOREIGN KEY FK_7D3656A4C54C8C93');
        $this->addSql('ALTER TABLE bill DROP FOREIGN KEY FK_7A2119E31DBF857F');
        $this->addSql('ALTER TABLE cost DROP FOREIGN KEY FK_182694FC2B1C2395');
        $this->addSql('ALTER TABLE payment_method DROP FOREIGN KEY FK_7B61A1F62476A5D8');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993984584665A');
        $this->addSql('ALTER TABLE product_configuration DROP FOREIGN KEY FK_7F0FB9254584665A');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398D716A741');
        $this->addSql('ALTER TABLE product_configuration_snapshot DROP FOREIGN KEY FK_F6E95C8D716A741');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04ADB2A824D8');
        $this->addSql('ALTER TABLE product_snapshot DROP FOREIGN KEY FK_E7FD7FA2B2A824D8');
        $this->addSql('ALTER TABLE api_storage DROP FOREIGN KEY FK_B9446F53A76ED395');
        $this->addSql('ALTER TABLE bill DROP FOREIGN KEY FK_7A2119E3A76ED395');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE page_tracking_storage DROP FOREIGN KEY FK_4ABFD2D0A76ED395');
        $this->addSql('ALTER TABLE payment_method DROP FOREIGN KEY FK_7B61A1F6A76ED395');
        $this->addSql('ALTER TABLE user_data_snapshot DROP FOREIGN KEY FK_81D71B9A76ED395');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398F9EC24C9');
        $this->addSql('DROP TABLE account');
        $this->addSql('DROP TABLE account_type');
        $this->addSql('DROP TABLE api_storage');
        $this->addSql('DROP TABLE bill');
        $this->addSql('DROP TABLE cost');
        $this->addSql('DROP TABLE csrf_token_storage');
        $this->addSql('DROP TABLE frontend_error_storage');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE page_tracking_storage');
        $this->addSql('DROP TABLE payment_method');
        $this->addSql('DROP TABLE payment_method_type');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_configuration');
        $this->addSql('DROP TABLE product_configuration_snapshot');
        $this->addSql('DROP TABLE product_snapshot');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_data_snapshot');
    }
}
