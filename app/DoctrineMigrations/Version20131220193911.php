<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create a table to keep track of users courses
 */
class Version20131220193911 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
        CREATE  TABLE IF NOT EXISTS `users_courses` (
          `id` INT NOT NULL AUTO_INCREMENT ,
          `user_id` INT NULL ,
          `course_id` INT NOT NULL ,
          `offering_id` INT NULL ,
          `created` TIMESTAMP NULL,
          `list_id` INT NOT NULL ,
          PRIMARY KEY (`id`) ,
          INDEX `fk_users_courses_user_id_idx` (`user_id` ASC) ,
          INDEX `fk_users_courses_course_id_idx` (`course_id` ASC) ,
          INDEX `fk_users_courses_offering_id_idx` (`offering_id` ASC) ,
          INDEX `multi_users_courses_user_course_idx` (`user_id` ASC, `course_id` ASC) ,
          CONSTRAINT `fk_users_courses_user_id`
            FOREIGN KEY (`user_id` )
            REFERENCES `users` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_users_courses_course_id`
            FOREIGN KEY (`course_id` )
            REFERENCES `courses` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_users_courses_offering_id`
            FOREIGN KEY (`offering_id` )
            REFERENCES `offerings` (`id` )
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
