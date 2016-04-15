<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160414224405 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
        ALTER TABLE courses
        ADD COLUMN price INT NOT NULL,
        ADD COLUMN price_period VARCHAR(2) NOT NULL,
        ADD COLUMN certificate_price INT NULL,
        ADD COLUMN workload_type VARCHAR(2) NULL,
        ADD COLUMN duration_min INT NULL,
        ADD COLUMN duration_max INT NULL,
        ADD COLUMN duration_type VARCHAR(2) NULL
        ");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
