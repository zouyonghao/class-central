<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150503170810 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
            ALTER TABLE courses
              ADD COLUMN duplicate_course_id INT(11) NULL,
              ADD CONSTRAINT fk_course_duplicate_course_id
                FOREIGN KEY (duplicate_course_id)
                REFERENCES courses (id)
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
