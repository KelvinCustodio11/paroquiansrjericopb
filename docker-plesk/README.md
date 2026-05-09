# Ambiente Local — Simulação Plesk (docker-plesk/)

Replica **fielmente** o servidor de produção Plesk Napoleon para que testes locais tenham o mesmo comportamento que em produção.

## Estrutura (idêntica ao servidor)

```
paroquia.local/              ← /var/www/vhosts/paroquia.local/
  httpdocs/                  ← site estático  (repo raiz)
  cms/                       ← CMS Laravel    (pasta cms/ do repo, FORA de httpdocs)
```

| Local               | Produção                              |
|---------------------|---------------------------------------|
| `http://localhost:8080` | `https://pascomjerico.com.br`     |
| `http://localhost:8081` | `https://admin.pascomjerico.com.br` |

## Primeiro uso

```bash
# 0. Criar symlink para cms/ (necessário uma única vez por máquina)
#    O docker-compose.yml monta ../../paroquia-cms como cms/ — que deve apontar
#    para /root/dev/paroquiansrjericopb/cms/
ln -sfn /root/dev/paroquiansrjericopb/cms /root/dev/paroquia-cms

# 1. Construir e subir
docker compose -f docker-plesk/docker-compose.yml up -d --build

# 2. Inicializar CMS (uma única vez)
docker compose -f docker-plesk/docker-compose.yml exec plesk bash /setup.sh

# 3. Criar usuário admin (se setup.sh indicar "0 usuários")
docker compose -f docker-plesk/docker-compose.yml exec plesk \
  /opt/plesk/php/8.2/bin/php /var/www/vhosts/paroquia.local/cms/artisan make:filament-user
```

## Uso cotidiano

```bash
# Subir (sem rebuild)
docker compose -f docker-plesk/docker-compose.yml up -d

# Parar
docker compose -f docker-plesk/docker-compose.yml down

# Logs Apache em tempo real
docker compose -f docker-plesk/docker-compose.yml logs -f

# Rodar comando artisan
docker compose -f docker-plesk/docker-compose.yml exec plesk \
  /opt/plesk/php/8.2/bin/php /var/www/vhosts/paroquia.local/cms/artisan <comando>
```

## Rebuild após alterar Dockerfile ou docker-compose.yml

```bash
docker compose -f docker-plesk/docker-compose.yml up -d --build --force-recreate
```

## Como as permissões funcionam

O `docker-entrypoint.sh` roda **antes do Apache** e faz `chown -R www-data:www-data`
nas pastas que o CMS precisa gravar (`data/`, `artigos/`, `eventos/`, `homilias/`,
`images/uploads/`, `partials/`, `css/` e os `*.html` raiz). Isso torna `www-data`
**dono** dessas pastas — arquivos criados ou reescritos pelo CMS continuam pertencendo
a `www-data`, evitando `Permission denied` em publicações subsequentes.

Em caso de problemas de permissão persistentes, basta reiniciar o container
(o entrypoint reaplicará o `chown`):

```bash
docker compose -f docker-plesk/docker-compose.yml restart
```

Ou forçar rebuild completo:

```bash
docker compose -f docker-plesk/docker-compose.yml up -d --build --force-recreate
```

> **Não use** `chmod 777` em arquivos do bind-mount — isso contamina o `fileMode`
> do git e vai aparecer como diff em todos os arquivos modificados.

## Arquivos desta pasta

| Arquivo                  | Descrição                                          |
|--------------------------|----------------------------------------------------|
| `Dockerfile`             | Imagem PHP 8.2 + Apache sem Node.js (igual Plesk)  |
| `docker-compose.yml`     | Orquestração de serviços e bind-mounts             |
| `.env.docker`            | Variáveis de ambiente do CMS para o container      |
| `apache/httpdocs.conf`   | VirtualHost :80 — site estático                    |
| `apache/cms.conf`        | VirtualHost :81 — CMS Admin                        |
| `setup.sh`               | Inicialização do CMS (migrations, permissões etc.) |
| `debug-node.sh`          | Diagnóstico de ausência de Node.js                 |
