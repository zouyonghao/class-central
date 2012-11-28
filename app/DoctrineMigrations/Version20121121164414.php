<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
    Doctrine\DBAL\Schema\Schema;

/**
 * Adds a column search_desc to offering table on which additional text
 * on which courses could be searched on would be stored
 */
class Version20121121164414 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql( "ALTER TABLE offerings ADD COLUMN search_desc TEXT");  

    }

    public function down(Schema $schema)
    {
        $this->throwIrreversibleMigrationException();
    }
}
