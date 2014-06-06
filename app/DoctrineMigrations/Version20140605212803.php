<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add fields to collect information about certificate, verified certificate,and workload to courses table
 */
class Version20140605212803 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE courses ADD COLUMN certificate BOOLEAN null"
        );

        $this->addSql(
            "ALTER TABLE courses ADD COLUMN verified_certificate BOOLEAN null"
        );

        $this->addSql(
            "ALTER TABLE courses ADD COLUMN workload_min INT null"
        );

        $this->addSql(
            "ALTER TABLE courses ADD COLUMN workload_max INT null"
        );


    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
