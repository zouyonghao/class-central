<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150704001659 extends AbstractMigration
{
    public function up(Schema $schema)
    {
       $this->addSql("
       CREATE TABLE IF NOT EXISTS `credentials_reviews` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `credential_id` INT NOT NULL,
          `user_id` INT NULL,
          `rating` FLOAT NOT NULL,
          `title` TEXT NULL,
          `text` TEXT NULL,
          `status` INT NOT NULL,
          `progress` INT NOT NULL,
          `date_completed` DATE NULL,
          `link` TEXT NULL,
          `topic_coverage` FLOAT NULL,
          `job_readiness` FLOAT NULL,
          `support` FLOAT NULL,
          `effort` INT NULL,
          `duration` INT NULL,
          `reviewer_name` VARCHAR(255) NULL,
          `reviewer_email` VARCHAR(255) NULL,
          `reviewer_job_title` VARCHAR(255) NULL,
          `reviewer_highest_degree` VARCHAR(255) NULL,
          `reviewer_field_of_study` VARCHAR(255) NULL,
          `created` TIMESTAMP NULL,
          `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          INDEX `fk_credentials_reviews_credential_id_idx` (`credential_id` ASC),
          INDEX `fk_credentials_reviews_user_id_idx` (`user_id` ASC),
          CONSTRAINT `fk_credentials_reviews_credential_id`
            FOREIGN KEY (`credential_id`)
            REFERENCES `credentials` (`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_credentials_reviews_user_id`
            FOREIGN KEY (`user_id`)
            REFERENCES `users` (`id`)
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
