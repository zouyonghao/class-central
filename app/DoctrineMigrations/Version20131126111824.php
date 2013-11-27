<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Added new columns to initiative, institutions, streams table for adding headers
 */
class Version20131126111824 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE initiatives ADD COLUMN image_url TEXT");

        $this->addSql("ALTER TABLE institutions ADD COLUMN description TEXT");
        $this->addSql("ALTER TABLE institutions ADD COLUMN image_url TEXT");

        $this->addSql("ALTER TABLE streams ADD COLUMN description TEXT");
        $this->addSql("ALTER TABLE streams ADD COLUMN image_url TEXT");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
