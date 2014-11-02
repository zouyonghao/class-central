<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a field 'key' to the File entity. The user of the Kuber API
 * can set this key. This can be used to detect changes
 * in the API field.
 */
class Version20141102133944 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
          ALTER TABLE files
          ADD COLUMN `unique_key` VARCHAR(100) null
       ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
