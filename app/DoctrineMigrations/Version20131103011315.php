<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds isVerified column to email and users table
 */
class Version20131103011315 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE emails ADD COLUMN `isVerified` TINYINT(1) NOT NULL DEFAULT 0;"
        );

        $this->addSql(
            "ALTER TABLE users ADD COLUMN `isVerified` TINYINT(1) NOT NULL DEFAULT 0;"
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
