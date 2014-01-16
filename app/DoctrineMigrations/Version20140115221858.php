<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adding a slug column to language table
 */
class Version20140115221858 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("ALTER TABLE languages ADD COLUMN `slug` VARCHAR(45) NOT NULL");
        $this->addSql("ALTER TABLE languages ADD COLUMN `code` VARCHAR(45) NOT NULL");


        // Update the columns
        $this->addSql("UPDATE  languages SET code='en', slug ='english' WHERE name='English' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='fr', slug ='french' WHERE name='French' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='es', slug ='spanish' WHERE name='Spanish' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='it', slug ='italian' WHERE name='Italian' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='zh', slug ='chinese' WHERE name='Chinese' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='de', slug ='german' WHERE name='German' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='ar', slug ='arabic' WHERE name='Arabic' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='ru', slug ='russian' WHERE name='Russian' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='tr', slug ='turkish' WHERE name='Turkish' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='pt', slug ='portuguese' WHERE name='Portuguese' LIMIT 1");


        $this->addSql("
            ALTER TABLE languages
            ADD CONSTRAINT languages_code_unique UNIQUE (code)
        ");

        $this->addSql("
            ALTER TABLE languages
            ADD CONSTRAINT languages_slug_unique UNIQUE (slug)
        ");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
