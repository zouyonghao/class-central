<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds the reviews_summaries table where the summaries for reviews are stored
 */
class Version20150129142012 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // Create the reviews summaries table
        $this->addSql(
            "CREATE TABLE IF NOT EXISTS `reviews_summaries` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `review_id` INT NOT NULL,
              `summary` TEXT NOT NULL,
              `created` TIMESTAMP NOT NULL,
              PRIMARY KEY (`id`),
              INDEX `fk_reviews_summaries_review_id_idx` (`review_id` ASC),
              CONSTRAINT `fk_reviews_summaries_reviews`
                FOREIGN KEY (`review_id`)
                REFERENCES `reviews` (`id`)
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB;"
        );

        // Add a review_summary_id to the reviews table
        $this->addSql(
            " ALTER TABLE reviews
              ADD COLUMN `review_summary_id` INT NULL DEFAULT NULL
            "
        );

        // Add a foreign key constraint
        $this->addSql(
          "ALTER TABLE reviews
            ADD CONSTRAINT `fk_reviews_reviews_summary`
            FOREIGN KEY (`review_summary_id`)
            REFERENCES `reviews_summaries` (`id`)
          "
        );

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
