<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration to create tables for keeping track of newsletters
 */
class Version20131026175807 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // Create newsletters table
        $this->addSql("
          CREATE  TABLE IF NOT EXISTS `newsletters` (
          `id` INT NOT NULL AUTO_INCREMENT ,
          `name` VARCHAR(255) NOT NULL ,
          `code` VARCHAR(45) NOT NULL ,
          `title` TEXT NULL ,
          `description` TEXT NULL ,
          PRIMARY KEY (`id`) ,
          UNIQUE INDEX `code_UNIQUE` (`code` ASC) )
          ENGINE = InnoDB;
          "
        );

        // Create emails table
        $this->addSql("
          CREATE  TABLE IF NOT EXISTS `emails` (
          `id` INT NOT NULL AUTO_INCREMENT ,
          `email` VARCHAR(255) NOT NULL ,
          `created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ,
          PRIMARY KEY (`id`) ,
          UNIQUE INDEX `email_UNIQUE` (`email` ASC) )
          ENGINE = InnoDB;
        ");

        // Crate table for keeping track of newsletter subscriptions
        $this->addSql(
           "CREATE  TABLE IF NOT EXISTS `newsletters_subscriptions` (
          `id` INT NOT NULL AUTO_INCREMENT ,
          `email_id` INT NULL ,
          `user_id` INT NULL ,
          `newsletter_id` INT NOT NULL ,
          `created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ,
          PRIMARY KEY (`id`) ,
          INDEX `fk_newsletters_subscriptions_user_id_idx` (`user_id` ASC) ,
          INDEX `fk_newsletters_subscriptions_newsletter_id_idx` (`newsletter_id` ASC) ,
          INDEX `fk_newsletters_subscriptions_email_id_idx` (`email_id` ASC) ,
          INDEX `newsletters_subscriptions_email_newsletter_idx` (`email_id` ASC, `newsletter_id` ASC) ,
          INDEX `newsletters_subscriptions_user_newsletter_idx` (`user_id` ASC, `newsletter_id` ASC) ,
          CONSTRAINT `fk_newsletters_subscriptions_user_id`
            FOREIGN KEY (`user_id` )
            REFERENCES `users` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_newsletters_subscriptions_newsletter_id`
            FOREIGN KEY (`newsletter_id` )
            REFERENCES `newsletters_subscriptions` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT `fk_newsletters_subscriptions_email_id`
            FOREIGN KEY (`email_id` )
            REFERENCES `emails` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ENGINE = InnoDB;"
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
