<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add courses_subjects table
 */
class Version20170508041234 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("
        CREATE TABLE IF NOT EXISTS courses_subjects (
          id INT NOT NULL AUTO_INCREMENT,
          course_id INT NOT NULL,
          subject_id INT NOT NULL,
          PRIMARY KEY (id),
          INDEX fk_courses_subjects_course_id_idx (course_id ASC),
          INDEX fk_courses_subjects_sujbect_id_idx (subject_id ASC),
          UNIQUE INDEX key_courses_subjects_unique (course_id ASC, subject_id ASC),
          CONSTRAINT fk_courses_subjects_course_id
            FOREIGN KEY (course_id)
            REFERENCES courses (id)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION,
          CONSTRAINT fk_courses_subjects_subject_id
            FOREIGN KEY (subject_id)
            REFERENCES streams (id)
            ON DELETE NO ACTION
            ON UPDATE NO ACTION)
        ENGINE = InnoDB;
        ");

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
