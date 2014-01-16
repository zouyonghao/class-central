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
        // Add column which specifies the subject color
        $this->addSql("ALTER TABLE languages
            ADD COLUMN `color` CHAR(7) NULL
        ");

        // Add column for display order
        $this->addSql("ALTER TABLE languages
            ADD COLUMN `display_order` INT NULL DEFAULT 0
        ");

        // Update the columns
        $this->addSql("UPDATE  languages SET code='en', slug ='english',color='#f15f45' WHERE name='English' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='fr', slug ='french',color='#1663a3' WHERE name='French' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='es', slug ='spanish',color='#5ca8a3' WHERE name='Spanish' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='it', slug ='italian',color='#ce2127' WHERE name='Italian' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='zh', slug ='chinese',color='#3a759a' WHERE name='Chinese' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='de', slug ='german',color='#788988' WHERE name='German' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='ar', slug ='arabic',color='#6e8898' WHERE name='Arabic' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='ru', slug ='russian',color='#ca393e' WHERE name='Russian' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='tr', slug ='turkish',color='#1663a3' WHERE name='Turkish' LIMIT 1");
        $this->addSql("UPDATE  languages SET code='pt', slug ='portuguese',color='#3a759a' WHERE name='Portuguese' LIMIT 1");


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
