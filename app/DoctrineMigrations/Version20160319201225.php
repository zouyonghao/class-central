<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create tables related to careers
 */
class Version20160319201225 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS categories (
              id INT NOT NULL AUTO_INCREMENT,
              name VARCHAR(255) NOT NULL,
              slug VARCHAR(100) NOT NULL,
              PRIMARY KEY (id))
            ENGINE = InnoDB;
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS careers (
              id INT NOT NULL AUTO_INCREMENT,
              name VARCHAR(255) NOT NULL,
              slug VARCHAR(100) NOT NULL,
              category_id INT NOT NULL,
              PRIMARY KEY (id),
              INDEX fk_careers_category_idx (category_id ASC),
              CONSTRAINT fk_careers_category
                FOREIGN KEY (category_id)
                REFERENCES categories (id)
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
