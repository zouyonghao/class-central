<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds a feedback summary table for reviews. This will be precomputed.
 */
class Version20140128163632 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
       CREATE  TABLE IF NOT EXISTS `reviews_feedback_summary` (
          `id` INT NOT NULL AUTO_INCREMENT ,
          `review_id` INT NOT NULL ,
          `positive` INT NOT NULL ,
          `negative` INT NOT NULL ,
          `total` INT NOT NULL DEFAULT 0 ,
          `created` TIMESTAMP NULL ,
          `modified` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
          PRIMARY KEY (`id`) ,
          INDEX `fk_reviews_feedback_summary_reviews_idx` (`review_id` ASC) ,
          UNIQUE INDEX `uniqure_reviews_feedback_summary_reviews_idx` (`review_id` ASC) ,
          CONSTRAINT `fk_reviews_feedback_summary_reviews`
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
