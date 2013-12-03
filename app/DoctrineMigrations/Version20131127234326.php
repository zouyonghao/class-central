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

        // Add column which specifies the subject color
        $this->addSql("ALTER TABLE streams
            ADD COLUMN `color` CHAR(7) NULL
        ");

        // Add column which specifies the subjects childrens' color
        $this->addSql("ALTER TABLE streams
            ADD COLUMN `child_color` CHAR(7) NULL
        ");

        // Add column for display order
        $this->addSql("ALTER TABLE streams
            ADD COLUMN `display_order` INT NULL DEFAULT 0
        ");

        $status = 0; // Hardcoding status
        $this->addSql("
            UPDATE courses SET status = $status WHERE status is NULL;
        ");

        // Delete subjects which are not being used
        $this->addSql("DELETE FROM streams where show_in_nav is NULL");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
