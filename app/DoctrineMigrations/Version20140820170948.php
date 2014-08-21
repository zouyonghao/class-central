<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creating a files table to store information about the files
 */
class Version20140820170948 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
        CREATE TABLE IF NOT EXISTS `files` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `entity` VARCHAR(50) NOT NULL,
          `type` VARCHAR(50) NOT NULL COMMENT 'Type of file - i.e. Profile Pic',
          `entity_id` VARCHAR(50) NOT NULL COMMENT 'Id of the entity i.e user_id',
          `file_name` VARCHAR(255) NOT NULL COMMENT 'Name of the file',
          `file_type` VARCHAR(50) NULL COMMENT 'Type of the file',
          PRIMARY KEY (`id`),
          INDEX `files_entity_type_id_idx` (`entity` ASC, `type` ASC, `entity_id` ASC))
        ENGINE = InnoDB;
       ");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
