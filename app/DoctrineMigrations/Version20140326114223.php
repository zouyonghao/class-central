<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the tables necessary for a tagging system
 */
class Version20140326114223 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Create a table to store all tags
        $this->addSql("
            CREATE  TABLE IF NOT EXISTS `tags` (
              `id` INT NULL AUTO_INCREMENT ,
              `name` VARCHAR(100) NOT NULL ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `name_UNIQUE` (`name` ASC) )
            ENGINE = InnoDB;
        ");

        // Create a table to store the course - tag association
        $this->addSql("
            CREATE  TABLE IF NOT EXISTS `courses_tags` (
              `courses_tags` INT NOT NULL AUTO_INCREMENT ,
              `course_id` INT NOT NULL ,
              `tag_id` INT NOT NULL ,
              PRIMARY KEY (`courses_tags`) ,
              INDEX `fk_courses_tags_course_id` (`course_id` ASC) ,
              INDEX `fk_courses_tages_tag_id` (`tag_id` ASC) ,
              UNIQUE INDEX `composite_courses_tags_idx_unique` (`course_id` ASC, `tag_id` ASC) ,
              CONSTRAINT `fk_courses_tags_course_id`
                FOREIGN KEY (`course_id` )
                REFERENCES `courses` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_courses_tages_tag_id`
                FOREIGN KEY (`tag_id` )
                REFERENCES `tags` (`id` )
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
