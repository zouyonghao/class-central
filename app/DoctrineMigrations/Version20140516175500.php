<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create a table to store text ads
 */
class Version20140516175500 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $con = $this->connection;

        $con->executeQuery("
        CREATE TABLE IF NOT EXISTS `text_ads` (
          `id` INT NOT NULL AUTO_INCREMENT,
          `title` CHAR(30) NOT NULL,
          `display_url` CHAR(40) NOT NULL,
          `description` CHAR(80) NOT NULL,
          `url` VARCHAR(255) NOT NULL,
          `visible` TINYINT(1) NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`))
        ENGINE = InnoDB;
        ");

        $con->insert('text_ads',array(
            'title' => 'Title 1',
            'display_url' => 'class-central.com',
            'description' => 'Description 1',
            'url' => "https://www.class-central.com/",
            'visible' => false
        ));

        $con->insert('text_ads',array(
            'title' => 'Title 2',
            'display_url' => 'class-central.com',
            'description' => 'Description 2',
            'url' => 'https://www.class-central.com/',
            'visible' => false
        ));
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
