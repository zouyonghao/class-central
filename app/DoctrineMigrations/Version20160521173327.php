<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add a Subject column to credential
 */
class Version20160521173327 extends AbstractMigration
{
    public function up(Schema $schema)
    {
       $this->addSql("
         ALTER TABLE credentials
           ADD stream_id int NULL
       ");

        $this->addSql("
            ALTER TABLE credentials
            ADD CONSTRAINT `fk_credentials_stream`
            FOREIGN KEY (`stream_id`)
            REFERENCES streams(`id`)
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
