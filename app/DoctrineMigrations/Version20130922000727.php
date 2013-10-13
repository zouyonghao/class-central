<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * This migration creates table for tracking courses and search terms added
 * to MOOC tracker
 */
class Version20130922000727 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("CREATE  TABLE IF NOT EXISTS `mooc_tracker_courses` (
            `id` INT NOT NULL AUTO_INCREMENT ,
          `user_id` INT NOT NULL ,
          `course_id` INT NOT NULL ,
          `created` TIMESTAMP NULL DEFAULT  CURRENT_TIMESTAMP ,
          PRIMARY KEY (`id`) ,
          INDEX `fk_mooc_tracker_courses_users_idx` (`user_id` ASC) ,
          INDEX `fk_mooc_tracker_courses_courses_idx` (`course_id` ASC) ,
          CONSTRAINT `fk_mooc_tracker_courses_users`
            FOREIGN KEY (`user_id` )
            REFERENCES `users` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_mooc_tracker_courses_courses`
            FOREIGN KEY (`course_id` )
            REFERENCES `courses` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
          ENGINE = InnoDB;");

        $this->addSql("CREATE  TABLE IF NOT EXISTS `mooc_tracker_search_terms` (
              `id` INT NOT NULL AUTO_INCREMENT ,
              `user_id` INT NOT NULL ,
              `search_term` VARCHAR(100) NOT NULL ,
              `created` TIMESTAMP NULL ,
              PRIMARY KEY (`id`) ,
              INDEX `fk_mooc_tracker_search_terms_user_idx` (`user_id` ASC) ,
              CONSTRAINT `fk_mooc_tracker_search_terms_user`
                FOREIGN KEY (`user_id` )
                REFERENCES `users` (`id` )
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
