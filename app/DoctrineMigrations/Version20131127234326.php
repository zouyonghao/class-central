<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add columns to make subjects(streams)/subject
 */
class Version20131127234326 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // add a column that points to a parent stream if it exists
        $this->addSql("ALTER TABLE streams
         ADD COLUMN`parent_stream_id` INT(11) NULL DEFAULT NULL");

        // make it a foreign key
        $this->addSql("ALTER TABLE streams
        ADD  CONSTRAINT `fk_parent_stream_id`
        FOREIGN KEY (`parent_stream_id` )
        REFERENCES `streams` (`id` )");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
