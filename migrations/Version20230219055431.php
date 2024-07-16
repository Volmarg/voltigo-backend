<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230219055431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE point_product DROP name, DROP price, DROP modified, DROP created, DROP deleted, DROP active, CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE point_product ADD CONSTRAINT FK_649EFD61BF396750 FOREIGN KEY (id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE point_product_snapshot DROP name, DROP price, DROP modified, DROP created, CHANGE id id INT NOT NULL');
        $this->addSql('ALTER TABLE point_product_snapshot ADD CONSTRAINT FK_1698404BF396750 FOREIGN KEY (id) REFERENCES product_snapshot (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }
}
