<?php

namespace Application\Migrations;

use ClassCentral\SiteBundle\Entity\Spotlight;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add dummy values for the MOOC Report section
 */
class Version20140624002249 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $con = $this->connection;
        for($position = 13; $position <= 17; $position++)
        {
            $con->insert('spotlights',array(
                'position' => $position,
                'title' => 'Spotlight title #' . $position,
                'description' => 'Welcome to the world of tomorrow',
                'url' => 'https://www.class-central.com',
                'image_url' => 'https://lorempixel.com/198/160/?'.$position,
                'type' => Spotlight::SPOTLIGHT_TYPE_NEWS
            ));
        }
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
