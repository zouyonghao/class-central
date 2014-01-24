<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creating table for reviews/rating system
 */
class Version20140117171805 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            CREATE  TABLE IF NOT EXISTS `reviews` (
              `id` INT NOT NULL AUTO_INCREMENT ,
              `rating` FLOAT NOT NULL ,
              `review` TEXT NOT NULL ,
              `hours` INT NULL ,
              `difficulty_id` INT NULL ,
              `level_id` INT NULL ,
              `user_id` INT NOT NULL ,
              `list_id` INT NULL ,
              `course_id` INT NOT NULL ,
              `offering_id` INT NULL ,
              `created` TIMESTAMP NULL DEFAULT NULL ,
              `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
              PRIMARY KEY (`id`) ,
              INDEX `fk_reviews_user_id_idx` (`user_id` ASC) ,
              INDEX `fk_reviews_course_idx` (`course_id` ASC) ,
              INDEX `fk_reviews_offering_idx` (`offering_id` ASC) ,
              CONSTRAINT `fk_reviews_user`
                FOREIGN KEY (`user_id` )
                REFERENCES `users` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_reviews_course`
                FOREIGN KEY (`course_id` )
                REFERENCES `courses` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_reviews_offering`
                FOREIGN KEY (`offering_id` )
                REFERENCES `offerings` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
          ENGINE = InnoDB;
        ");

        $this->addSql("
        CREATE  TABLE IF NOT EXISTS `reviews_feedback` (
          `id` INT NOT NULL AUTO_INCREMENT ,
          `user_id` INT NOT NULL ,
          `review_id` INT NOT NULL ,
          `helpful` TINYINT(1) NOT NULL ,
          `created` TIMESTAMP NULL DEFAULT NULL ,
          PRIMARY KEY (`id`) ,
          INDEX `fk_reviews_helpful_user_id_idx` (`user_id` ASC) ,
          INDEX `fk_reviews_helpful_review_id_idx` (`review_id` ASC) ,
          UNIQUE INDEX `multiple_user_review_idx` (`user_id` ASC, `review_id` ASC) ,
          CONSTRAINT `fk_reviews_helpful_user_id`
            FOREIGN KEY (`user_id` )
            REFERENCES `users` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_reviews_helpful_review_id`
            FOREIGN KEY (`review_id` )
            REFERENCES `reviews` (`id` )
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
