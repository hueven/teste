# Executar Migração de Projetos

## Opção 1: Via Navegador (Recomendado)

1. Acesse no navegador:
   ```
   http://seu-site.com/run_migration.php
   ```

2. Você verá o resultado da migração no navegador

3. **IMPORTANTE**: Após executar, DELETE o arquivo `run_migration.php` por segurança


## Opção 2: Via Linha de Comando

```bash
cd "w:\SITE FOTOGRAFIA"
php run_migration.php
```

## Opção 3: Via phpMyAdmin

1. Abra o phpMyAdmin
2. Selecione o banco `u704340876_fotografia`
3. Vá na aba "SQL"
4. Cole o conteúdo do arquivo `sql/projects_migration.sql`
5. Clique em "Executar"

## Verificação

Após executar, verifique se foi criado:
- Tabela `projects` com 11 colunas
- Coluna `project_id` na tabela `works`
- Coluna `is_hero` na tabela `works`

## Rollback (se necessário)

Se precisar reverter as alterações, use a seção de ROLLBACK no final do arquivo `sql/projects_migration.sql`
