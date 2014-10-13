<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a private field to the profile
 */
class Version20141012221304 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("
          ALTER TABLE users
           ADD COLUMN `is_private` TINYINT(1) NULL DEFAULT 0
       ");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
