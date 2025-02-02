<?php declare(strict_types = 1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180226035348 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE invoice CHANGE user_id user_id INT DEFAULT NULL, CHANGE status_id status_id INT DEFAULT NULL, CHANGE phone phone VARCHAR(255) DEFAULT NULL, CHANGE email email VARCHAR(255) DEFAULT NULL, CHANGE paid_at paid_at DATETIME DEFAULT NULL, CHANGE uri uri VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE patronymic patronymic VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE invoice CHANGE user_id user_id INT DEFAULT NULL, CHANGE status_id status_id INT DEFAULT NULL, CHANGE phone phone VARCHAR(255) NOT NULL COLLATE utf8_general_ci, CHANGE email email VARCHAR(255) NOT NULL COLLATE utf8_general_ci, CHANGE paid_at paid_at DATETIME DEFAULT \'NULL\', CHANGE uri uri VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8_general_ci');
        $this->addSql('ALTER TABLE user CHANGE patronymic patronymic VARCHAR(255) DEFAULT \'NULL\' COLLATE utf8_general_ci');
    }
}
