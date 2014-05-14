<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Alter table to add longDesc and syllabus fields to the course table
 */
class Version20140513234904 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
        ALTER TABLE courses
         ADD COLUMN long_description TEXT NULL
       ");

        $this->addSql("
        ALTER TABLE courses
         ADD COLUMN syllabus TEXT NULL
       ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
