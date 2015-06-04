<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 *  Adding a interviews table
 */
class Version20150604142000 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
        CREATE TABLE IF NOT EXISTS `interviews` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `summary` TEXT NOT NULL,
          `title` TEXT NOT NULL,
          `instructor_name` TEXT NOT NULL,
          `instructor_photo` TEXT NOT NULL,
          `created` TIMESTAMP NULL,
          `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`))
        ENGINE = InnoDB;
        ");

        $this->addSql("
             ALTER TABLE courses
              ADD COLUMN interview_id INT(11) NULL,
              ADD CONSTRAINT fk_course_interview_id
                FOREIGN KEY (interview_id)
                REFERENCES interviews (id)

        ");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
