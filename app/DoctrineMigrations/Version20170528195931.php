<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a suggested field to Follows table
 */
class Version20170528195931 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE follows
             ADD COLUMN `suggested` BOOLEAN DEFAULT FALSE             
            "
        );

        $this->addSql("
            ALTER TABLE profiles
            ADD COLUMN  `created` TIMESTAMP NULL,
            ADD COLUMN  `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ");

        $this->addSql("
            ALTER TABLE files
            ADD COLUMN  `created` TIMESTAMP NULL,
            ADD COLUMN  `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
