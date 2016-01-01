<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create a follows table
 */
class Version20151231170001 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
       CREATE TABLE IF NOT EXISTS follows (
          id INT NOT NULL AUTO_INCREMENT,
          item CHAR(15) NOT NULL,
          item_id INT NOT NULL,
          created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          user_id INT NOT NULL,
          PRIMARY KEY (id),
          INDEX follows_item_idx (item ASC),
          UNIQUE INDEX follows_item_item_idx (item ASC, item_id ASC),
          INDEX fk_follows_user_id_idx (user_id ASC),
          CONSTRAINT fk_follows_user_id
            FOREIGN KEY (user_id)
            REFERENCES users (id)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ENGINE = InnoDB;

        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
