<?php

declare(strict_types=1);

namespace app\database\migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505211424 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Users';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('users');

        $table->addColumn('id',            'bigint',   ['autoincrement' => true]);
        $table->addColumn('nome',          'text',     ['notnull' => true]);
        $table->addColumn('senha',         'text',     ['notnull' => true]);
        $table->addColumn('salario',       'decimal',  ['precision' => 18, 'scale' => 4, 'default' => 0]);
        $table->addColumn('sobrenome',     'text',     ['notnull' => true]);
        $table->addColumn('rg',            'text',     ['notnull' => true]);
        $table->addColumn('cpf',           'text',     ['notnull' => true]);
        $table->addColumn('email',         'text',     ['notnull' => true, 'default' => '']);
        $table->addColumn('google_id',     'text',     ['notnull' => false, 'default' => null]);
        $table->addColumn('ativo',         'boolean',  ['default' => false]);
        $table->addColumn('administrador', 'boolean',  ['default' => false]);
        $table->addColumn('criado_em',     'datetime', ['default' => 'CURRENT_TIMESTAMP']);
        $table->addColumn('atualizado_em', 'datetime', ['default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email'],     'users_email_unique');
        $table->addUniqueIndex(['google_id'], 'users_google_id_unique');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('users');
    }
}