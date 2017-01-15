<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a table to save and record follow counts
 */
class Version20170114234626 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
           CREATE TABLE IF NOT EXISTS follow_counts (
              id INT NOT NULL AUTO_INCREMENT,
              item CHAR(15) NOT NULL,
              item_id INT NOT NULL,
              followed INT DEFAULT 0,
              `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
              PRIMARY KEY (id),
              INDEX follow_counts_item_idx (item ASC),
              UNIQUE INDEX follow_counts_item_item_idx (item ASC, item_id ASC) )                           
            ENGINE = InnoDB;
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
