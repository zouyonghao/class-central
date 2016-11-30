<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a table to store Google auth
 */
class Version20161130110318 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
        CREATE  TABLE IF NOT EXISTS `users_google` (
          `id` INT NOT NULL AUTO_INCREMENT ,
          `user_id` INT NOT NULL ,
          `google_id` VARCHAR(45) NOT NULL ,
          `access_token` VARCHAR(45) NULL ,
          `google_email` VARCHAR(100) NULL ,
          `user_info` TEXT NULL ,
          `created` TIMESTAMP NULL ,
          `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
          PRIMARY KEY (`id`) ,
          INDEX `google_users_google_user_id` (`user_id` ASC) ,
          CONSTRAINT `google_users_google_user_id`
            FOREIGN KEY (`user_id` )
            REFERENCES `users` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ENGINE = InnoDB
        COMMENT = 'Contains users Google auth information';
       ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
