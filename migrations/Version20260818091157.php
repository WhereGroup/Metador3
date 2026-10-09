<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260818091157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_export DROP min');
        $this->addSql('ALTER TABLE import_export DROP year');
        $this->addSql('ALTER TABLE import_export DROP day_of_week');
        $this->addSql('ALTER TABLE import_export DROP month');
        $this->addSql('ALTER TABLE import_export DROP day_of_month');
        $this->addSql('ALTER TABLE import_export DROP hour');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE import_export ADD min VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_export ADD year VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_export ADD day_of_week VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_export ADD month VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_export ADD day_of_month VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE import_export ADD hour VARCHAR(10) DEFAULT NULL');
    }
}
