<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180715002649 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE company (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, contact_name VARCHAR(255) NOT NULL, creation_date DATETIME NOT NULL, active TINYINT(1) NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_request (id INT AUTO_INCREMENT NOT NULL, company_id INT NOT NULL, type VARCHAR(255) NOT NULL, colour VARCHAR(255) NOT NULL, cost DOUBLE PRECISION NOT NULL, quantity INT NOT NULL, date DATETIME NOT NULL, status VARCHAR(255) NOT NULL, dispatch VARCHAR(255) DEFAULT NULL, INDEX IDX_EA4E942F979B1AD6 (company_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE roof_tile (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, cost DOUBLE PRECISION NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE product_request ADD CONSTRAINT FK_EA4E942F979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product_request DROP FOREIGN KEY FK_EA4E942F979B1AD6');
        $this->addSql('DROP TABLE company');
        $this->addSql('DROP TABLE product_request');
        $this->addSql('DROP TABLE roof_tile');
    }
}
