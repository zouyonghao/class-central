<?php

namespace Application\Migrations;

use ClassCentral\SiteBundle\Entity\Spotlight;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Make entries for the blog spotlight section
 */
class Version20140512191123 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $con = $this->connection;

        $con->insert('spotlights',array(
            'position' => 100,
            'title' => 'Blog title #1',
            'description' => 'Welcome to the world of tomorrow',
            'url' => 'https://www.class-central.com',
            'image_url' => 'https://s3-us-west-2.amazonaws.com/ccprodhome/blog/post-bg-1.jpg',
            'type' => Spotlight::SPOTLIGHT_TYPE_BLOG
        ));

        $con->insert('spotlights',array(
            'position' => 101,
            'title' => 'Blog title #2',
            'description' => 'Welcome to the world of tomorrow',
            'url' => 'https://www.class-central.com',
            'image_url' => 'https://s3-us-west-2.amazonaws.com/ccprodhome/blog/post-bg-2.jpg',
            'type' => Spotlight::SPOTLIGHT_TYPE_BLOG
        ));

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
