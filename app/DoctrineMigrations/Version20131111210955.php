<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adds a column subject to the newsletter. This is the subject of the newsletter sent
 */
class Version20131111210955 extends AbstractMigration
{
    public function up(Schema $schema)
    {
      // Create a table to keep track of when the newsletters are sent
      $this->addSql("
       CREATE  TABLE IF NOT EXISTS `newsletter_log` (
      `id` INT NOT NULL AUTO_INCREMENT ,
      `newsletter_id` INT NOT NULL ,
      `sent` TIMESTAMP NOT NULL ,
      `created` TIMESTAMP NOT NULL ,
      PRIMARY KEY (`id`) ,
      INDEX `fk_newsletter_log_newsletter_id_idx` (`newsletter_id` ASC) ,
      CONSTRAINT `fk_newsletter_log_newsletter_id`
        FOREIGN KEY (`newsletter_id` )
        REFERENCES `newsletters` (`id` )
        ON DELETE NO ACTION
        ON UPDATE NO ACTION)
      ENGINE = InnoDB;
       ");

      // Update newsletter table
      $this->addSql(
          "ALTER TABLE newsletters ADD COLUMN subject VARCHAR(100) NOT NULL DEFAULT ''"
      );

    $this->addSql(
        "ALTER TABLE newsletters ADD COLUMN `frequency` INT NOT NULL DEFAULT 30 COMMENT 'Number of days'"
    );

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
