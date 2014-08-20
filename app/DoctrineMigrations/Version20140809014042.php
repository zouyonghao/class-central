<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds a new profile table
 */
class Version20140809014042 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // Create profile table
        $this->addSql("
            CREATE TABLE IF NOT EXISTS `profiles` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `user_id` INT NOT NULL,
              `about_me` TEXT NULL,
              `location` VARCHAR(255) NULL,
              `field_of_study` VARCHAR(255) NULL,
              `highest_degree` VARCHAR(255) NULL,
              `twitter` VARCHAR(255) NULL,
              `coursera` VARCHAR(255) NULL,
              `website` VARCHAR(255) NULL,
              `gplus` VARCHAR(255) NULL,
              `linkedin` VARCHAR(255) NULL,
              `facebook` VARCHAR(255) NULL,
               photo VARCHAR(255) NULL,
              PRIMARY KEY (`id`),
              INDEX `fk_profiles_user_id_idx` (`user_id` ASC),
              CONSTRAINT `fk_profiles_user_id`
            FOREIGN KEY (`user_id`)
            REFERENCES `users` (`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
            ENGINE = InnoDB;
       ");

        // Add a username column to the users table
        $this->addSql("
            ALTER TABLE users
            ADD COLUMN handle VARCHAR(25) NULL UNIQUE
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
