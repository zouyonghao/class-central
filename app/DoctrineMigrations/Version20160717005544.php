<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create a collections table
 */
class Version20160717005544 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
       CREATE TABLE IF NOT EXISTS collections (
          id INT NOT NULL AUTO_INCREMENT,
          title VARCHAR(255) NOT NULL,
          created TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          slug VARCHAR(100) NOT NULL,
          PRIMARY KEY (id))
        ENGINE = InnoDB;
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
