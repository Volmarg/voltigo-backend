<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240618093118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add api users';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO `api_user` (`id`, `username`, `roles`) VALUES
            (1,	'finances-hub',	'[]'),
            (2,	'job-offers-handler',	'[]'),
            (3,	'grafana',	'[]');
        ");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM api_user");
    }
}
