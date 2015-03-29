<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration to make it easy to choose course spotlight from database
 */
class Version20150328233735 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE courses
            ADD COLUMN oneliner VARCHAR(150) NULL,
            ADD COLUMN thumbnail VARCHAR(255) NULL DEFAULT '198*160 image used in the spotlight'
        ");

        $this->addSql("
          ALTER TABLE spotlights
            ADD COLUMN course_id INT NULL,
            ADD CONSTRAINT fk_spotlight_course_id
              FOREIGN KEY (course_id)
              REFERENCES courses (id)
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
