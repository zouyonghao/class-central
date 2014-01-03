<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the user migration table
 */
class Version20140103012415 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("CREATE  TABLE IF NOT EXISTS `user_preferences` (
          `id` INT NOT NULL AUTO_INCREMENT ,
          `type` SMALLINT unsigned NOT NULL ,
          `user_id` INT NOT NULL ,
          `value` VARCHAR(255) NOT NULL ,
          `created` TIMESTAMP NULL DEFAULT NULL ,
          `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
          PRIMARY KEY (`id`) ,
          UNIQUE INDEX `user_prefrences_composite_unique_user_id_type_idx` (`user_id` ASC, `type` ASC) ,
          INDEX `fk_user_prefrences_user_id_idx` (`user_id` ASC) ,
          CONSTRAINT `fk_user_prefrences_user_id`
            FOREIGN KEY (`user_id` )
            REFERENCES `users` (`id` )
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
      ENGINE = InnoDB;");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
