<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160311144857 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE activities ADD CONSTRAINT FK_B5F1AFE5AEC7D346 FOREIGN KEY (petition_id) REFERENCES micropetitions (id)');
        $this->addSql('CREATE INDEX IDX_B5F1AFE5AEC7D346 ON activities (petition_id)');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');
        
        $this->addSql('ALTER TABLE activities DROP FOREIGN KEY FK_B5F1AFE5AEC7D346');
        $this->addSql('DROP INDEX IDX_B5F1AFE5AEC7D346 ON activities');
    }
}
