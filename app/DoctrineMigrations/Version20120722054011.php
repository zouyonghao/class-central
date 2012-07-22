<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your need!
 */
class Version20120722054011 extends AbstractMigration
{
    public function up(Schema $schema)
    {   
        $this->addSql( "ALTER TABLE offerings ADD COLUMN short_name VARCHAR(50)");    
        $this->addSql( "CREATE UNIQUE INDEX offerings_short_name_unique ON offerings(short_name)" );
    }

    public function down(Schema $schema)
    {
        $this->throwIrreversibleMigrationException();
    }
}
