<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create credentials tables
 */
class Version20150628191501 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // Credential table
        $this->addSql("
            CREATE TABLE IF NOT EXISTS `credentials` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `name` TEXT NOT NULL,
              `one_liner` VARCHAR(255) NULL,
              `price` INT NOT NULL COMMENT 'In Dollars',
              `price_period` VARCHAR(2) NOT NULL COMMENT 'Monthly, Total ',
              `duration_min` INT NULL COMMENT 'In Months',
              `duration_max` INT NULL COMMENT 'In Months',
              `workload_min` INT NULL COMMENT 'Hours per week.',
              `workload_max` INT NULL COMMENT 'Hours per week',
              `url` TEXT NOT NULL COMMENT 'Hours per week',
              `description` TEXT NULL,
              `initiative_id` INT NULL,
              `created` TIMESTAMP NULL,
              `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              INDEX `fk_credentials_initiative_id_idx` (`initiative_id` ASC),
              CONSTRAINT `fk_credentials_initiative_id`
                FOREIGN KEY (`initiative_id`)
                REFERENCES `initiatives` (`id`)
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB;
        ");

        $this->addSql("
        CREATE TABLE IF NOT EXISTS `credentials_institutions` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `credential_id` INT NOT NULL,
          `institution_id` INT NOT NULL,
          PRIMARY KEY (`id`),
          INDEX `fk_credentials_institutions_credential_id_idx` (`credential_id` ASC),
          INDEX `fk_credentials_institutions_institution_id_idx` (`institution_id` ASC),
          CONSTRAINT `fk_credentials_institutions_credential_id`
            FOREIGN KEY (`credential_id`)
            REFERENCES `credentials` (`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_credentials_institutions_institution_id`
            FOREIGN KEY (`institution_id`)
            REFERENCES `institutions` (`id`)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ENGINE = InnoDB;
        ");

        $this->addSql("
            CREATE TABLE IF NOT EXISTS `credentials_courses` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `credential_id` INT NOT NULL,
              `course_id` INT NOT NULL,
              `order` INT NOT NULL DEFAULT 0,
              PRIMARY KEY (`id`),
              INDEX `fk_credentials_courses_credential_id_idx` (`credential_id` ASC),
              INDEX `fk_credentials_courses_course_id_idx` (`course_id` ASC),
              CONSTRAINT `fk_credentials_courses_credential_id`
                FOREIGN KEY (`credential_id`)
                REFERENCES `credentials` (`id`)
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_credentials_courses_course_id`
                FOREIGN KEY (`course_id`)
                REFERENCES `courses` (`id`)
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
