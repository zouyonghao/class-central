<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20150909214848 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql("
            ALTER TABLE credentials
            ADD COLUMN     sponsored TINYINT(1) NULL DEFAULT 0,
            ADD COLUMN     enrollment_start DATE NULL,
            ADD COLUMN     enrollment_end DATE NULL,
            ADD COLUMN     start_date DATE NULL,
            ADD COLUMN     end_date DATE NULL,
            ADD COLUMN     sub_title VARCHAR(100) NULL
        ");
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
