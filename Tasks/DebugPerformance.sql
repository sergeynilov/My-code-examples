

    Time(in ms) 0.56 :



Time(in ms) 1.44 :
SELECT *
FROM `users`


    Time(in ms) 0.46 :
SELECT *
FROM `tasks`



Time(in ms) 1.04 :
DELETE
FROM `tasks`
WHERE `id` = 6




Time(in ms) 0.58 :
SELECT *
FROM `users`


    Time(in ms) 0.45 :
SELECT *
FROM `tasks`




Time(in ms) 1.47 :
SELECT *
FROM `users`

    Time(in ms) 0.47 :
SELECT *
FROM `tasks`

Time(in ms)  :
   ROLLBACK;


Time(in ms) 1.35 :
SELECT *
FROM `users`


    Time(in ms) 0.48 :
SELECT *
FROM `tasks`


Time(in ms) 0.37 :
SELECT *
FROM `users`
WHERE `users`.`id` in (34)


    Time(in ms) 0.37 :
SELECT `id`
FROM `tasks`
WHERE `tasks`.`parent_id` = 10


    Time(in ms) 1.64 :
SELECT *
FROM `users`



    Time(in ms) 0.47 :
SELECT *
FROM `tasks`
