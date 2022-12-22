# Verificador de agenda - Embaixada
Software utilizado para verificar agenda do site da embaixada americana. 
Com ele é possível verificar se foi disponibilizada uma data mais recente para sua entrevista de visto.

## Requisitos 

- Chrome web driver
- PHP
- Composer
- Credenciais de bot do Telegram

### Chrome web driver
Faça o download do [Chrome web driver](https://chromedriver.chromium.org/downloads) e o instale.

Após a instalação, você pode inicializar o driver com o seguinte comando:

```sh
$ chromedriver --port=4444
Starting ChromeDriver 108.0.5359.124 (603c1cb86aff29563721da2a6351c0d08865350d-refs/branch-heads/5359@{#1179}) on port 4444
Only local connections are allowed.
Please see https://chromedriver.chromium.org/security-considerations for suggestions on keeping ChromeDriver safe.
ChromeDriver was started successfully.
```
O parâmetro `--port` determina em que porta o driver irá ser executado.

### Credenciais de bot do Telegram
Para gerar credenciais de bot do telegram, você deve falar com [@BotFather](https://telegram.me/botfather).
Envie `/start` e em seguida `/newbot`. Serão solicitadas algumas informações sobre o seu bot. Após este processo, você receberá suas credenciais.

```txt
Done! Congratulations on your new bot. You will find it at t.me/<botname>. You can now add a description, about section and profile picture for your bot, see /help for a list of commands. By the way, when you've finished creating your cool bot, ping our Bot Support if you want a better username for it. Just make sure the bot is fully operational before you do this.

Use this token to access the HTTP API:
<API ID>:<API HASH>
Keep your token secure and store it safely, it can be used by anyone to control your bot.

For a description of the Bot API, see this page: https://core.telegram.org/bots/api
```

Você precisará preencher o arquivo `.env` com o HTTP API.

> 🚩 Envie uma mensagem para o seu bot. Isso será necessário para saber quem tem que receber os alertas da aplicação. 

### Preparando a aplicação

#### Instale as dependencias
A aplicação utiliza composer como gestor de dependencias. Basta executar o comando:

```sh
$ composer install
```

#### Preencha o arquivo `.env`
Você pode utilizar  arquivo `.env.example` como base para gerar o seu `.env`.

```txt
# credenciais de acesso ao site da embaixada
USR_EMAIL=<your user email>
USR_PASSWD=<your user password>

# token do telegram (<id>:<hash>)
TELEGRAM_TOKEN=<your bot token>

# endereço onde o webdriver está sendo execurado
WEBDRIVER_LOCATION=<chrome webdriver location>
```

## Executando o projeto
Com o webdrive rodando, execute o arquivo `index.php`:

```sh
$ php index.php
```