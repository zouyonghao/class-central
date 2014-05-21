<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Modify the review feedback to allow anonymous review
 */
class Version20140520211442 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql(
            "ALTER TABLE reviews_feedback MODIFY COLUMN user_id int(11) null"
        );

        $this->addSql(
            "ALTER TABLE reviews_feedback ADD column session_id VARCHAR(50) NULL"
        );
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
