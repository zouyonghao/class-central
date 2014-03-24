<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adding files to the courses and offering tables to define the sessions
 *
 */
class Version20140323185858 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE offerings
             ADD COLUMN `state` INT NULL DEFAULT 0 COMMENT 'State represents the status of the course by time - in progress, finished, self faced, recent, etc.'
            "
        );

        $this->addSql(
            "ALTER TABLE courses
             ADD COLUMN next_session_id INT(11) NULL
            "
        );

        $this->addSql(
            "ALTER TABLE courses
             ADD CONSTRAINT `courses_next_session_id`
              FOREIGN KEY (`next_session_id` )
              REFERENCES `offerings` (`id` )
            "
        );

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
