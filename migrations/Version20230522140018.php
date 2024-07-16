<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522140018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address CHANGE zip zip LONGTEXT NOT NULL, CHANGE street street LONGTEXT NOT NULL, CHANGE city city LONGTEXT NOT NULL, CHANGE home_number home_number LONGTEXT NOT NULL, CHANGE country country VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE address_snapshot CHANGE zip zip LONGTEXT NOT NULL, CHANGE street street LONGTEXT NOT NULL, CHANGE home_number home_number LONGTEXT NOT NULL, CHANGE country country VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE page_tracking_storage ADD COLUMN route_name LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD COLUMN description LONGTEXT NOT NULL');


        $this->addSql('ALTER TABLE user CHANGE firstname firstname LONGTEXT NOT NULL, CHANGE lastname lastname LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE user_data_snapshot CHANGE email email LONGTEXT NOT NULL, CHANGE username username LONGTEXT NOT NULL, CHANGE firstname firstname LONGTEXT NOT NULL, CHANGE lastname lastname LONGTEXT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
