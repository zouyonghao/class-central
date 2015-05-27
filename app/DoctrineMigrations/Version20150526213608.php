<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create a table to store indepth reviews
 */
class Version20150526213608 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE TABLE IF NOT EXISTS `indepth_reviews` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `user_id` INT(11) NOT NULL,
              `course_id` INT(11) NOT NULL,
              `summary` TEXT NOT NULL,
              `url` VARCHAR(255) NOT NULL,
              `rating` INT NOT NULL,
              `created` TIMESTAMP NULL,
              `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              INDEX `fk_indepth_reviews_user_idx` (`user_id` ASC),
              INDEX `fk_indepth_reviews_courses_idx` (`course_id` ASC),
              CONSTRAINT `fk_indepth_reviews_users`
                FOREIGN KEY (`user_id`)
                REFERENCES `cc_dev`.`users` (`id`)
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_indepth_reviews_courses`
                FOREIGN KEY (`course_id`)
                REFERENCES `cc_dev`.`courses` (`id`)
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
