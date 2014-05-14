<?php

namespace Application\Migrations;

use ClassCentral\SiteBundle\Entity\Spotlight;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create a table for managing the spotlight section
 */
class Version20140506002035 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $con = $this->connection;
        // this up() migration is auto-generated, please modify it to your needs
        $con->executeQuery(
            "CREATE TABLE IF NOT EXISTS `spotlights` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `position` INT NOT NULL,
              `title` TEXT NOT NULL,
              `description` TEXT NOT NULL,
              `url` TEXT NOT NULL,
              `image_url` TEXT NOT NULL,
              `type` INT NOT NULL,
              PRIMARY KEY (`id`))
            ENGINE = InnoDB;"
        );

        // Insert dummy data
       for($position = 1; $position <= 10; $position++)
       {
           $con->insert('spotlights',array(
                'position' => $position,
                'title' => 'Spotlight title #' . $position,
                'description' => 'Welcome to the world of tomorrow',
                'url' => 'https://www.class-central.com',
                'image_url' => 'https://lorempixel.com/198/160/?'.$position,
                'type' => Spotlight::SPOTLIGHT_TYPE_DEMO
           ));
       }

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
