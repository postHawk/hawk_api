# API FOR SERVICE [POST HAWK](https://post-hawk.com)

Для того, чтобы воспользоваться услугами сервиса Вам необходимо сделать три шага.

1. [Зарегистрироваться](https://post-hawk.com/auth/)
1. Добавить домен (ы) в настройках учётной записи
1. Скачать и подключить файлы апи.

На стороне сервера. Используется класс **php hawk_api**.

* Каждого пользователя, у которого должна быть возможность передачи сообщений необходимо зарегистрировать в системе с помощью метода апи: **register_user ($id)**.
* Каждый пользователь должен иметь уникальный идентификатор. Не **рекомендуется использовать**, например, логин, гораздо предпочтительней будет его **md5 хэш**.
* Идентификатор пользователя должен быть **строкой от 3 до 64 символов, состоящей из букв латинского алфавита и чисел** (удовлетворять регулярному выражению **/^[a-zA-Z\d]{3,64}$/**).
* Для связи с системой post hawk задействуется **модуль curl или sockets**, хотя бы один из этих модулей **должен быть активирован**, в противном случае использовать систему не удастся.
Пример использования php апи:

```
#!php

<?php
require_once 'api/php/hawk_api.php';
use \hawk_api\hawk_api;

//создаём объект апи
$api_key = 'ключ, полученный после регистрации';
$api = new hawk_api($api_key);
$u1 = md5('user1');
$u2 = md5('user2');
?>
```
## Базовый функционал работы с пользователями ##

```
#!php
<?php
//регистрируем пользователя в системе
//рекомендуется делать при авторизации пользователя
$api->register_user($u1);
$api->register_user($u2);
//удаляем регистрацию пользователя
//рекомендуется делать при деавторизации пользователя
$api->unregister_user($u2);
?>
```

## Функционал работы с группами ##

```
#!php
<?php
$g1 = md5('group1');
$g2 = md5('group2');
$g3 = md5('group3');

//добавление пользователя в группу
$api->add_user_to_group('test2', array($g1, $g2));
$api->add_user_to_group('test', array($g1, $g2));
//удаление пользователя из группы
$api->remove_user_from_group('test', array($g2);
//отправка группового сообщения
$api->seng_group_message('test', 'message to group', array($g1, $g2));
//получение пользователей в группе
json_decode($api->get_user_by_group(array($g1)));
json_decode($api->get_user_by_group(array($g2)));
json_decode($api->get_user_by_group(array($g1, $g2)));
?>
```

На стороне клиента. Используется объект HAWK_API.

* Клиентский браузер **должен поддерживать технологию WebSockets**
* Необходимо подключить **jQuery** и библиотеку апи
* При событии готовности документа необходимо вызвать метод **init** с параметром в виде объекта, в поле **user_id** которого, содержится идентификатор, ранее переданный системе с помощью метода **register_user ($id)**.
* Подписаться на события с помощью метода
```
#!javascript

HAWK_API.bind_handler ('event', function (e, msg) {})
```
, где первый параметр — название события, а второй — колбэк, вызываемый при наступлении этого события. Поддерживаются следующие события:

* **hawk.open** — соединение с сервером установлено
* **hawk.msg_sended** — сообщение отправлено
* **hawk.message** — поступило новое сообщение
* **hawk.close** — соединение с сервером закрыто
* **hawk.socket_error** — ошибка соединения с сервером
* **hawk.server_error** — сервер сгенерировал ошибку

Пример использования клиентского апи:

	<!DOCTYPE html>
	<html>
	    <head>
	        <title>Демо</title>
	        <script type="text/javascript" src="api/js/jquery.js"></script>
	        <script type="text/javascript" src="api/js/hawk_api.js"></script>
	        <script type="text/javascript">


```
#!javascript

	            $(document).ready(function(){
	                HAWK_API.init({
	                        user_id: 'hash' //идентификатор пользователя, который зарегистрирован в системе
	                });
	                $('#send').click(function(){
	                    var text = $('#to_m').val();
	                    if(text.trim() != '')
	                    {
	                        //эти данные будут переданы в неизменном виде через систему, пользователю, указанному в "to"
	                        //из-за ограничений erlang менять порядок свойств или добавлять новые, запрещается
	                        //можно изменять только их содержимое
	                        var m = JSON.stringify({
	                            from: HAWK_API.get_user_id(),//от кого сообщение
	                            to: {
									user: $('#to_u').val(), // пользователю
									group: ['group1'] //группе/группам
								}, //кому сообщение, должен быть хотя бы один из этих пунктов
	                            time: Math.floor(new Date / 1000), //дата сообщения
	                            text: text //текст сообщения
	                        });

	                        //отправка сообщения
	                        HAWK_API.send_message(m);
	                        $('#messages').append('<div>' + HAWK_API.get_user_id() + ': ' + text + '</div>');
	                    }
	                });

	                //подписываемся на новые сообщения
	                HAWK_API.bind_handler('hawk.message', function(e, msg) {
	                    $('#messages').append('<div>' + msg.from + ': ' + msg.text + '</div>');
	                });
	            });
```


	        </script>
	    </head>
	    <body>
	        <div id="messages" style="width: 500px; height: 250px; border: 2px solid green;overflow: auto;"></div>
	        Кому: <input id="to_u" type="text"><br>
	        Текст: <textarea id="to_m"></textarea><br>
	        <input type="button" id="send" value="Отправить">
	    </body>
	</html>