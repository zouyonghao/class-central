<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a table to store fb oauth info
 */
class Version20140205183358 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
        CREATE  TABLE IF NOT EXISTS `users_fb` (
          `id` INT NOT NULL AUTO_INCREMENT ,
          `user_id` INT NOT NULL ,
          `fb_id` VARCHAR(45) NOT NULL ,
          `access_token` VARCHAR(45) NULL ,
          `fb_email` VARCHAR(100) NULL ,
          `user_info` TEXT NULL ,
          `created` TIMESTAMP NULL ,
          `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
          PRIMARY KEY (`id`) ,
          INDEX `fk_users_fb_user_id` (`user_id` ASC) ,
          CONSTRAINT `fk_users_fb_user_id`
            FOREIGN KEY (`user_id` )
            REFERENCES `users` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ENGINE = InnoDB
        COMMENT = 'Contains users facebook information';
       ");

        $this->addSql(
            "ALTER TABLE users ADD COLUMN `signup_type` INT NULL DEFAULT 1 COMMENT '// SIGNUP FORM, Facebook, etc' "
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
