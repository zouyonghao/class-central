<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20140213161335 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // extends the name of the short_name column
        $this->addSql("ALTER TABLE offerings MODIFY COLUMN short_name VARCHAR(255)");

        // Updates the shortname for future learn courses
        $this->addSql("UPDATE offerings SET short_name = CONCAT('fl-',SUBSTRING(url,37)) WHERE course_id IN (SELECT id FROM courses WHERE initiative_id=113);");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
