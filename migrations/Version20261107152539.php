<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261107152539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create order table with user relationship.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, recipient_name VARCHAR(100) NOT NULL, phone VARCHAR(30) NOT NULL, address VARCHAR(255) NOT NULL, notes VARCHAR(255) DEFAULT NULL, total NUMERIC(10, 2) NOT NULL, status VARCHAR(20) DEFAULT \'placed\' NOT NULL, payment_method VARCHAR(20) DEFAULT \'cod\' NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_F5299398A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('DROP TABLE `order`');
    }
}
