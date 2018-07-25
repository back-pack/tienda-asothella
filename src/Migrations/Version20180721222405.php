<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180721222405 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product_request ADD CONSTRAINT FK_EA4E942F7B576F77 FOREIGN KEY (requirement_id) REFERENCES requirement (id)');
        $this->addSql('CREATE INDEX IDX_EA4E942F7B576F77 ON product_request (requirement_id)');
        $this->addSql('ALTER TABLE requirement ADD company_id INT NOT NULL');
        $this->addSql('ALTER TABLE requirement ADD CONSTRAINT FK_DB3F5550979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id)');
        $this->addSql('CREATE INDEX IDX_DB3F5550979B1AD6 ON requirement (company_id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE product_request DROP FOREIGN KEY FK_EA4E942F7B576F77');
        $this->addSql('DROP INDEX IDX_EA4E942F7B576F77 ON product_request');
        $this->addSql('ALTER TABLE requirement DROP FOREIGN KEY FK_DB3F5550979B1AD6');
        $this->addSql('DROP INDEX IDX_DB3F5550979B1AD6 ON requirement');
        $this->addSql('ALTER TABLE requirement DROP company_id');
    }
}
