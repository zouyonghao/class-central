<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a src column to the reviews table
 */
class Version20141003174629 extends AbstractMigration
{
    public function up(Schema $schema)
    {
            $this->addSql("
            ALTER TABLE reviews
            ADD COLUMN  `source` VARCHAR(45) NULL DEFAULT 'website'
           ");

            $this->addSql("
            ALTER TABLE reviews
            ADD COLUMN `is_rating` TINYINT(1) NULL DEFAULT 0
          ");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
