<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160320115747 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
        CREATE TABLE IF NOT EXISTS courses_careers (
          id INT NOT NULL AUTO_INCREMENT,
          course_id INT NOT NULL,
          career_id INT NOT NULL,
          PRIMARY KEY (id),
          INDEX fk_courses_careers_course_id_idx (course_id ASC),
          INDEX fk_courses_careers_career_id_idx (career_id ASC),
          UNIQUE INDEX key_courses_careers_unique (course_id ASC, career_id ASC),
          CONSTRAINT fk_courses_careers_course_id
            FOREIGN KEY (course_id)
            REFERENCES courses (id)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT fk_courses_careers_career_id
            FOREIGN KEY (career_id)
            REFERENCES careers (id)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ENGINE = InnoDB;
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
