<div align="center"><h1>Voltigo - backend</h1></div>

<div align="center">
<img src="https://img.shields.io/badge/php%208.1-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white"/>
<img src="https://img.shields.io/badge/Rabbitmq-FF6600?style=for-the-badge&logo=rabbitmq&logoColor=white"/>
<img src="https://img.shields.io/badge/mysql-4479A1.svg?style=for-the-badge&logo=mysql&logoColor=white"/>
<img src="https://img.shields.io/badge/supervisor-%23777BB4.svg?style=for-the-badge&logoColor=white"/>
<img src="https://img.shields.io/badge/docker-%230db7ed.svg?style=for-the-badge&logo=docker&logoColor=white"/>
<img src="https://img.shields.io/badge/composer-%2366595C.svg?style=for-the-badge&logo=composer&Color=white"/>
<img src="https://img.shields.io/badge/symfony-%23000000.svg?style=for-the-badge&logo=symfony&logoColor=white"/>
</div>

<div align="center">
This project is a part of: <b><a href="">Voltigo</a></b>
</div>

## Description

This project is a backend of the <b><a href="">Voltigo</a></b>, it takes all the users requests, communicate with other projects,
returns the data back to the user etc.

# Running the project

- go inside the `docker` directory,
- call `docker-compose docker-compose-prod.yaml -f up -d`
- the project is now reachable:
   - locally under: `127.0.0.1:8001`
   - within other voltigo-related containers under: `host.docker.internal:8001` 

## Other notes

### Debugging websocket connection

- See: `https://github.com/websockets/wscat`
- Install `wscat` via `sudo npm i -g wscat`
- Start connection as a client with `wscat ws://127.0.0.1:8654`

### Some websocket docs

- Websocket error codes:
   - https://libwebsockets.org/lws-api-doc-main/html/group__wsclose.html#:~:text=1007%20indicates%20that%20an%20endpoint,data%20within%20a%20text%20message).
   - https://datatracker.ietf.org/doc/html/rfc6455#section-7.4.1