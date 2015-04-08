$(document).ready(function () {

	//подписываемся на события
	HAWK_API.bind_handler('hawk.open', function (e, msg) {
		add_log('<span style="color: green"><b>соединение с сервером установлено</b></span>');
	});
	HAWK_API.bind_handler('hawk.msg_sended', function (e, msg) {
		add_log('<span style="color: blue"><b>сообщение ' + JSON.stringify(msg) + ' отправлено</b></span>');
	});
	HAWK_API.bind_handler('hawk.message', function (e, msg) {
		add_log('<span style="color: blue"><b>поступило новое сообщение: ' + JSON.stringify(msg) + '</b></span>');
		window.console.debug(msg);
		var date = new Date(msg.text.time*1000);
		$("#messages").append('<div><small>['
				+ date.getDate() + '.'
				+ date.getMonth() + '.'
				+ date.getFullYear() + ' '
				+ date.getHours() + ':'
				+ date.getMinutes()
				+ ']</small> <b>(' + msg.text.from_login + ')</b>: ' + msg.text.message + '</div>');
	});
	HAWK_API.bind_handler('hawk.close', function (e, msg) {
		add_log('<span style="color: red"><b>соединение с сервером закрыто</b></span>');
	});
	HAWK_API.bind_handler('hawk.socket_error', function (e, msg) {
		add_log('<span style="color: red"><b>ошибка соединения с сервером</b></span>');
	});
	HAWK_API.bind_handler('hawk.server_error', function (e, msg) {
		add_log('<span style="color: red"><b>сервер сгенерировал ошибку: ' + msg + '</b></span>');
	});

	//делаем окошко перетаскиваемым
	$('#chat').draggable({containment: "#wrapper",
		scroll: false,
		handle: "#header"});

	//если у юзера нет логина, то просим ввести его
	if (!CONTROL_OBJ.user_login)
	{
		set_login();
	}
	else
	{
		//инициализируем подключение к сервису
		HAWK_API.init({
			user_id: CONTROL_OBJ.user_id,
			url: CONTROL_OBJ.server_url
		});
	}

	$('#out_text').keyup(function (e) {
		if (e.ctrlKey === true && e.keyCode === 13)
		{
			var text = $('#out_text').html();
			if (text.trim() !== '')
			{
				$('#out_text').html('');
				var m = {
					to: {
						group: [CONTROL_OBJ.group_id]
					},
					text: {
						from_login: CONTROL_OBJ.user_login,
						message: text,
						time: Math.floor(new Date / 1000),
					}
				};

				HAWK_API.send_message(m);
			}
		}
	});

	get_online();
	setInterval(get_online, 30000);
});

/**
 * метод добавляет пользователя в чат
 * @param {object} u пользователь
 * @returns {void}
 */
function add_user_in_chat(u)
{
	var st = 'offline';
	if(u.online)
	{
		st = 'online';
	}

	$('#' + st + '_u').append('<div class="' + st + '" id="' + u.user
			+ '" style="margin-left: 5px; font-size: 14px;"><b>' + ((u.login) ? u.login : u.user) + '</b></div>');
}

/**
 * метод добавляет запись в лог
 * @param {string} str
 * @returns {void}
 */
function add_log(str)
{
	$('#action_log').append(str + '<br>');
}

/**
 * метод устанавливает логин текущего пользователя
 * @returns {undefined}
 */
function set_login()
{
	var login = prompt("Укажите ваш логин в чате \r\n(допускаются латинские буквы, цифры и символ _)");
	if (!login || login.trim() === '')
	{
		set_login();
		return;
	}

	//отправляем логин на сервер и если всё хорошо инициализируем подключение
	$.post(document.location.href, {
		action: 'set_login',
		login: login
	},
	function (data) {
		data = JSON.parse(data);
		if (data['error'] === 'invalid_login')
		{
			alert('Логин не корректен, попробуйте еще раз');
			set_login();
		}
		else
		{
			HAWK_API.init({
				user_id: CONTROL_OBJ.user_id,
				url: CONTROL_OBJ.server_url
			});

			CONTROL_OBJ.user_login = login;
			add_user_in_chat({user: login, online: true});
		}
	});
}

/**
 * метод получает пользователей из сервиса
 * @returns {void}
 */
function get_online()
{
	//прсим пользователей у сервера и обновляем список в чате
	$.post(document.location.href, {
		action: 'get_online'
	},
	function (data) {
		data = JSON.parse(data);
		$('#online_u').empty();
		$('#offline_u').empty();
		if(data.result)
		{
			var users = [];
			for (var key in data.result)
			{
				var u = data.result[key];
				if (u.user && $.inArray(u.user, users) === -1)
				{
					add_user_in_chat(u);
					users.push(u);
				}
			}
		}
	});
}