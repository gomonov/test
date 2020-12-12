# Тестовое
### Запуск
Запускаем докер ```docker-compose up -d```
Стартанёт redis, php (подтянутся зависимости через composer) и supervisor (запустится 100 воркеров)
Количество воркеров настраивается в ```docker/supervisor/supervisord.conf``` (numprocs=100)

### Генерация:
```docker exec -ti test_php php bin/console event:generator```
***
Я не совсем понял текстовку в задании "События генерировать пачками, содержащими последовательности по 1-10 для каждого аккаунта.". 
Я сделал, чтобы рандомно выбирался пользователь (из 1000) и для него генерились события в количестве от 1 до 10 (тоже выбирается рандомно)
и так, пока общее количество событий не достигнет 10000 штук. В среднем, на большом количестве событий среднее количество событий для пользователя будет примерно равным 10 (при условии 10000 событий, 1000 пользователей)

### Принцип работы 
Принцип работы основан на том, что события каждого пользователя попадают в список **user_{id}** в redis. Консьмеры периодически пробегаются по спискам и выбирают "свободный для обработки"
Свободный - значит его ни кто не обрабатывает. Таким образом я гарантирую, что события от одного пользователя будут обработаны строго в том порядке, в котором поступили.
Механизм защиты от состояние гонки - инкремент воркером значения ключа **blockqueue_{id}**. Если метод вернул 1 - значит этот воркер первый, кто будет работать с этой очередью

Дополнительно реализован механизм защиты от того что воркер упадёт, и очередь останется "залоченой". 
Для этого на ключ с блокировкой **blockqueue_{id}** ставится ttl на 30 секунд. Это значение ставится в одной транзакции (атомрной операции) с функцией инкремента - опять же для защиты от падений воркера между инкрементом и установкой ttl

Дополнительно релизован механизм защиты сообщений в очереди, если воркер не сможет обработать сообщение. 
Своего рода (ack nack) - консьмер должен возвращать true или false. По умолчанию он возвращает true, но поведение можно переопределить настройкой в .env **NO_ERROR=0**, 
тогда true/false будет возвращаться рандомно

Мой результат при входных данных 10000 событий рандомно, но приблизительно равномерно раскиданы по 1000 пользователям - 121 секунда при 100 воркерах 

Логирование ведётся стандартно через монолог. ```var/log/event.log```

Кадое событие помечено расширеной меткой времени через функцию ```hrtime```
