<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Generate the table required for token verification
 */
class Version20131102211704 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql(
            "CREATE  TABLE IF NOT EXISTS `verification_tokens` (
              `id` INT NOT NULL AUTO_INCREMENT ,
              `token` VARCHAR(255) NULL ,
              `value` VARCHAR(255) NULL ,
              `created` TIMESTAMP NULL ,
              `expiry` INT NULL DEFAULT 1440 COMMENT 'in minutes since creation' ,
              PRIMARY KEY (`id`) ,
              UNIQUE INDEX `token_UNIQUE` (`token` ASC) )
            ENGINE = InnoDB;
            "
        );

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
