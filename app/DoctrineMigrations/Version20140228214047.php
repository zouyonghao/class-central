<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 *
 * Adding fields to reviews show external fields
 */
class Version20140228214047 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE reviews ADD COLUMN `external_link` VARCHAR(255) NULL"
        );

        $this->addSql(
            "ALTER TABLE reviews ADD COLUMN     `reviewer_name` VARCHAR(255) NULL"
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
