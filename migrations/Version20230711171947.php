<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230711171947 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Setting point products';
    }

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema): void
    {
        $this->addSql("
            INSERT INTO `point_shop_product` (`id`, `cost`, `name`, `internal_identifier`, `modified`, `created`) VALUES
            (1,	100,	'Job search tag - no limit',	'JOB_SEARCH_TAG_NO_LIMIT',	NULL,	CURRENT_TIMESTAMP),
            (2,	59,	'Job search tag - limit 30',	'JOB_SEARCH_TAG_LIMIT_30',	NULL,	CURRENT_TIMESTAMP),
            (3,	1,	'Email sending',	'EMAIL_SENDING',	NULL,	CURRENT_TIMESTAMP);
        ");

        $this->addSql("
            INSERT INTO `product` (`id`, `created`, `modified`, `name`, `description`, `base_currency_code`, `price`, `deleted`, `active`, `discr`) VALUES
            (1,	CURRENT_TIMESTAMP,	NULL,	'300 points',	'Grant 300 points',	'PLN',	18,	0,	1,	'pointproduct'),
            (2,	CURRENT_TIMESTAMP,	NULL,	'200 Points',	'Grant 200 points',	'PLN',	12,	0,	1,	'pointproduct'),
            (3,	CURRENT_TIMESTAMP,	NULL,	'1000 Points',	'Grant 1000 points','PLN',	60,	0,	1,	'pointproduct'),
            (4,	CURRENT_TIMESTAMP,	NULL,	'100 Points',	'Grant 100 points',	'PLN',	6,	0,	1,	'pointproduct'),
            (5,	CURRENT_TIMESTAMP,	NULL,	'500 points',	'Grant 500 points',	'PLN',	30,	0,	1,	'pointproduct'),
            (6,	CURRENT_TIMESTAMP,	NULL,	'50 Points',	'Grant 50 points',	'PLN',	3,	0,	1,	'pointproduct');
        ");

        $this->addSql("
            INSERT INTO `point_product` (`id`, `amount`) VALUES
            (1,	300),
            (2,	200),
            (3,	1000),
            (4,	100),
            (5,	500),
            (6,	50);
        ");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM point_product");
        $this->addSql("DELETE FROM product");
        $this->addSql("DELETE FROM point_shop_product");
    }
}
