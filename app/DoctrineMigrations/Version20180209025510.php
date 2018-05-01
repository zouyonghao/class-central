<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add tables related to help guides
 */
class Version20180209025510 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // help_guide_sections
        $this->addSql("
            CREATE  TABLE IF NOT EXISTS `help_guide_sections` (
              `id` INT NOT NULL AUTO_INCREMENT ,
              `name` VARCHAR(255) NOT NULL ,
              `description` TEXT NULL ,
              `slug` VARCHAR(50) NOT NULL,
              `created` TIMESTAMP NULL DEFAULT NULL ,
              `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`))            
            ENGINE = InnoDB;
        ");

        // help_guide_articles
        $this->addSql("
            CREATE  TABLE IF NOT EXISTS `help_guide_articles` (
              `id` INT NOT NULL AUTO_INCREMENT ,
              `title` VARCHAR(255) NOT NULL,
              `text` TEXT,
              `summary` TEXT,
              `order_id` INT NOT NULL DEFAULT 0,
              `slug` VARCHAR(50) NOT NULL,
              `created` TIMESTAMP NULL DEFAULT NULL ,
              `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP ,
              `author_id` INT NULL,  
              `status` INT NOT NULL,
              `section_id` INT NOT NULL,
              PRIMARY KEY (`id`),
              INDEX `fk_help_guide_articles_author_id` (`author_id` ASC),
              INDEX `fk_help_guide_articles_section_id` (`section_id` ASC),
              CONSTRAINT `fk_help_guide_articles_author_id`
                FOREIGN KEY (`author_id` )
                REFERENCES `users` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_help_guide_articles_section_id`
                FOREIGN KEY (`section_id` )
                REFERENCES `help_guide_sections` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)  
            ENGINE = InnoDB;
        ");

        $this->addSql("CREATE UNIQUE INDEX `help_guide_articles.slug` ON help_guide_articles(slug) ");
        
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
