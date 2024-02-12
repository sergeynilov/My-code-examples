Installation

1) To run docker in file __DOCKER/.env edit APP_PATH_HOST var - project root path


2) Under __DOCKER/ subdirectory run docker with  command :
docker compose up -d --build


3) Enter the docker bash with command :
docker exec -ti  Tasks_web   bash

You do not need to run manually composer under bash, just waite for 1-2 minutes to run composer on install automatically.
You can check if file /vendor/autoload.php created


4)  Check logs with commands 
docker logs --tail=50  -t    MysqlTasks_adminer
docker logs --tail=50  -t    Tasks_composer
docker logs --tail=50  -t    MySqlTasks_db
docker logs --tail=50  -t    Tasks_web


5) Check project environment with url
http://127.0.0.1:8088/info.php

in .env file of the project add parameters :

APP_URL=http://127.0.0.1:8088
DB_HOST=mysql_db
DB_PORT=3306
DB_DATABASE=DockerTasks
DB_USERNAME=docker_user
DB_PASSWORD=4321

Also you can fill .env.testing to run http tests


6) Check app root with url 
http://127.0.0.1:8088

7) Enter Adminer with url:
http://127.0.0.1:8089/

Use parameters for login :
System  	= MySQL
Server      = mysql_db
Username    = docker_user 
Password	= 4321
Database	= DockerTasks



8) In docker bash run migration with command :
php artisan migrate:fresh --seed


9) The are http tests, which can be run in docker bash 
vendor/bin/phpunit tests/Feature/TasksCrudTest.php   --testdox

and 

vendor/bin/phpunit tests/Feature/ReportsTest.php --testdox


10) I wrote a lot of comments througout the app.


11) I did not write any documents - telling the true I consider is is ok to spend 1-2 days on testing tasks.
I have spent on the project more time I expected from the start.
The whole work took about 4 working days.

Also if to write some documentation, I would like to see sample documentation used in your company
