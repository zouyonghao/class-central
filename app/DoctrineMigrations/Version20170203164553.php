<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adding country and continent column to institutions
 */
class Version20170203164553 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE institutions
             ADD COLUMN country VARCHAR(50) NULL,
             ADD COLUMN continent VARCHAR(50) NULL
            "
        );

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
