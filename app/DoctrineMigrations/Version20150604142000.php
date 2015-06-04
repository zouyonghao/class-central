<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 *  Adding a interviews table
 */
class Version20150604142000 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
        CREATE TABLE IF NOT EXISTS `interviews` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `summary` TEXT NOT NULL,
          `title` TEXT NOT NULL,
          `instructor_name` TEXT NOT NULL,
          `instructor_photo` TEXT NOT NULL,
          `course_id` INT NOT NULL,
          `created` TIMESTAMP NULL,
          `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          INDEX `fk_interviews_course_id_idx` (`course_id` ASC),
          CONSTRAINT `fk_interviews_course_id`
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
