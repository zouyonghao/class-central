<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * This migration will add the status field to offering table
 */
class Version20120229020932 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE offerings ADD COLUMN status SMALLINT DEFAULT 0");
        // Update the status column with the values  in exact_date_know columns
        $this->addSql("UPDATE offerings SET status=exact_dates_know");
        
    }

    public function down(Schema $schema)
    {
        $this->throwIrreversibleMigrationException();
    }
}
