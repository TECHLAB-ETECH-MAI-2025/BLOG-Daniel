<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608131009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__article_like AS SELECT id, article_id, created_at FROM article_like
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE article_like
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE article_like (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, article_id INTEGER DEFAULT NULL, user_id INTEGER NOT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
            , CONSTRAINT FK_1C21C7B27294869C FOREIGN KEY (article_id) REFERENCES article (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_1C21C7B2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO article_like (id, article_id, created_at) SELECT id, article_id, created_at FROM __temp__article_like
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__article_like
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1C21C7B27294869C ON article_like (article_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1C21C7B2A76ED395 ON article_like (user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__article_like AS SELECT id, article_id, created_at FROM article_like
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE article_like
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE article_like (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, article_id INTEGER DEFAULT NULL, created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
            , ip_address VARCHAR(45) NOT NULL, CONSTRAINT FK_1C21C7B27294869C FOREIGN KEY (article_id) REFERENCES article (id) NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO article_like (id, article_id, created_at) SELECT id, article_id, created_at FROM __temp__article_like
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__article_like
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1C21C7B27294869C ON article_like (article_id)
        SQL);
    }
}
