<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creating a table to store course recommendations
 */
class Version20140224154514 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE  TABLE IF NOT EXISTS `courses_recommendations` (
              `id` INT NOT NULL AUTO_INCREMENT ,
              `course_id` INT NOT NULL ,
              `recommended_course_id` INT NOT NULL ,
              `position` INT NULL DEFAULT 0 ,
              `created` TIMESTAMP NULL DEFAULT NULL ,
              `modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
              PRIMARY KEY (`id`) ,
              INDEX `fk_courses_recommendations_course_id` (`course_id` ASC) ,
              INDEX `fk_courses_recommendations_recommended_course_id` (`recommended_course_id` ASC) ,
              CONSTRAINT `fk_courses_recommendations_course_id`
                FOREIGN KEY (`course_id` )
                REFERENCES `courses` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_courses_recommendations_recommended_course_id`
                FOREIGN KEY (`recommended_course_id` )
                REFERENCES `courses` (`id` )
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
